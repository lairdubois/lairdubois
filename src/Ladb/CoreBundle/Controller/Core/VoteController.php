<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Model\VotableInterface;
use Ladb\CoreBundle\Model\VotableParentInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Utils\VotableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Entity\Core\Vote;
use Ladb\CoreBundle\Event\VotableEvent;
use Ladb\CoreBundle\Event\VotableListener;

/**
 * @Route("/votes")
 */
class VoteController extends Controller {

	/**
	 * @Route("/{entityType}/{entityId}/{sign}/create", requirements={"entityType" = "\d+", "entityId" = "\d+", "sign" = "[+-]"}, name="core_vote_create")
	 */
	public function createAction(Request $request, $entityType, $entityId, $sign) {

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $this->_retriveRelatedEntity($entityRepository, $entityId);

		// Retrieve related parent entity

		$parentEntityRepository = $this->_retrieveRelatedParentEntityRepository($entity);
		$parentEntity = $this->_retrieveRelatedParentEntity($parentEntityRepository, $entity);

		// Compute score
		$score = $sign == '+' ? 1 : -1;

		// Process vote

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		$vote = $voteRepository->findOneByEntityTypeAndEntityIdAndUser($entity->getType(), $entity->getId(), $this->getUser());
		if (is_null($vote)) {

			// Create a new vote
			$vote = new Vote();
			$vote->setEntityType($entityType);
			$vote->setEntityId($entityId);
			$vote->setParentEntityType($parentEntity->getType());
			$vote->setParentEntityId($parentEntity->getId());
			$vote->setParentEntityField($entity->getParentEntityField());
			$vote->setUser($this->getUser());
			$vote->setScore($score);

			$om->persist($vote);

			// Update related entity
			$entity->incrementVoteScore($score);
			$entity->incrementVoteCount();
			$parentEntity->incrementVoteCount();
			if ($score > 0) {
				$entity->incrementPositiveVoteScore($score);
				$parentEntity->incrementPositiveVoteCount();
				$this->getUser()->getMeta()->incrementPositiveVoteCount();
			} else {
				$entity->incrementNegativeVoteScore(abs($score));
				$parentEntity->incrementNegativeVoteCount();
				$this->getUser()->getMeta()->incrementNegativeVoteCount();
			}

		} else {

			if ($score != $vote->getScore()) {

				// Update related entity
				$entity->incrementVoteScore(-$vote->getScore() + $score);
				if ($vote->getScore() > 0) {
					$entity->incrementPositiveVoteScore(-$vote->getScore());
					$parentEntity->incrementPositiveVoteCount(-1);
					$this->getUser()->getMeta()->incrementPositiveVoteCount(-1);
				} else {
					$entity->incrementNegativeVoteScore(-abs($vote->getScore()));
					$parentEntity->incrementNegativeVoteCount(-1);
					$this->getUser()->getMeta()->incrementNegativeVoteCount(-1);
				}
				if ($score > 0) {
					$entity->incrementPositiveVoteScore($score);
					$parentEntity->incrementPositiveVoteCount();
					$this->getUser()->getMeta()->incrementPositiveVoteCount();
				} else {
					$entity->incrementNegativeVoteScore(abs($score));
					$parentEntity->incrementNegativeVoteCount();
					$this->getUser()->getMeta()->incrementNegativeVoteCount();
				}

				// Update vote
				$vote->setScore($score);

				// Delete activities
				$activityUtils = $this->get(ActivityUtils::NAME);
				$activityUtils->deleteActivitiesByVote($vote, false);

			} else {
				throw $this->createNotFoundException('Can\'t vote twice for the same Votable (core_vote_create)');
			}

		}

		// Dispatch votable parent event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(VotableListener::VOTE_UPDATED, new VotableEvent($entity, $parentEntity));

		// Create activity
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->createVoteActivity($vote, false);

		$om->flush();

		if ($request->isXmlHttpRequest()) {

			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Core/Vote:create-xhr.html.twig', array(
				'voteContext' => $votableUtils->getVoteContext($entity, $this->getUser()),
			));
		}

		// Return to

		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	private function _retriveRelatedEntityRepository($entityType) {

		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entityRepository = $typableUtils->getRepositoryByType($entityType);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}

		return $entityRepository;
	}

	private function _retriveRelatedEntity($entityRepository, $entityId) {

		$entity = $entityRepository->findOneById($entityId);
		if (is_null($entity)) {
			throw $this->createNotFoundException('Unknow Entity Id (entityId='.$entityId.').');
		}
		if (!($entity instanceof VotableInterface)) {
			throw $this->createNotFoundException('Entity must implements VotableInterface.');
		}
		if ($entity->getUser()->getId() == $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (vote->_retriveRelatedEntity)');
		}

		return $entity;
	}

	private function _retrieveRelatedParentEntityRepository($entity) {

		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$parentEntityRepository = $typableUtils->getRepositoryByType($entity->getParentEntityType());
		} catch (\Exception $e) {
			throw $this->createNotFoundException('Unknow Parent Entity Type (groupEntityType='.$entity->getParentEntityType().').');
		}

		return $parentEntityRepository;
	}

	private function _retrieveRelatedParentEntity($parentEntityRepository, $entity) {

		$parentEntity = $parentEntityRepository->findOneById($entity->getParentEntityId());
		if (is_null($parentEntity)) {
			throw $this->createNotFoundException('Unknow Parent Entity Id (entityId='.$entity->getParentEntityId().').');
		}
		if (!($parentEntity instanceof VotableParentInterface)) {
			throw $this->createNotFoundException('Parent Entity must implements VotableParentInterface.');
		}

		return $parentEntity;
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_vote_delete")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		$vote = $voteRepository->findOneById($id);
		if (is_null($vote)) {
			throw $this->createNotFoundException('Unable to find Vote entity (id='.$id.').');
		}
		if ($vote->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_vote_delete)');
		}

		$om->remove($vote);

		// Retrieve related entity

		$entityRepository = $this->_retriveRelatedEntityRepository($vote->getEntityType());
		$entity = $this->_retriveRelatedEntity($entityRepository, $vote->getEntityId());

		// Retrieve related parent entity

		$parentEntityRepository = $this->_retrieveRelatedParentEntityRepository($entity);
		$parentEntity = $this->_retrieveRelatedParentEntity($parentEntityRepository, $entity);

		// Update related entity

		if ($vote->getScore() > 0) {
			$entity->incrementPositiveVoteScore(-$vote->getScore());
			$parentEntity->incrementPositiveVoteCount(-1);
			$this->getUser()->getMeta()->incrementPositiveVoteCount(-1);
		} else {
			$entity->incrementNegativeVoteScore(-abs($vote->getScore()));
			$parentEntity->incrementNegativeVoteCount(-1);
			$this->getUser()->getMeta()->incrementNegativeVoteCount(-1);
		}
		$entity->incrementVoteScore(-$vote->getScore());
		$entity->incrementVoteCount(-1);
		$parentEntity->incrementVoteCount(-1);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByVote($vote, false);

		// Dispatch votable parent event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(VotableListener::VOTE_UPDATED, new VotableEvent($entity, $parentEntity));

		$om->flush();

		if ($request->isXmlHttpRequest()) {

			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Core/Vote:delete-xhr.html.twig', array(
				'voteContext' => $votableUtils->getVoteContext($entity, $this->getUser()),
			));
		}

		// Return to (use referer because the user is already logged)

		$returnToUrl = $request->headers->get('referer');

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/p/{entityType}/{entityId}", requirements={"entityType" = "\d+", "entityId" = "\d+"}, name="core_vote_list_parent_entity")
	 * @Route("/p/{entityType}/{entityId}/{filter}", requirements={"entityType" = "\d+", "entityId" = "\d+", "filter" = "[a-z-]+"}, name="core_vote_list_parent_entity_filter")
	 * @Route("/p/{entityType}/{entityId}/{filter}/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_vote_list_parent_entity_filter_page")
	 * @Template("LadbCoreBundle:Core/Vote:list-byparent.html.twig")
	 */
	public function showParentVotesAction(Request $request, $entityType, $entityId, $filter = 'positive', $page = 0) {

		// Retrieve related parent entity

		$entityRepository = $this->_retriveRelatedEntityRepository($entityType);
		$entity = $entityRepository->findOneById($entityId);
		if (is_null($entity)) {
			throw $this->createNotFoundException('Unknow Parent Entity Id (entityId='.$entityId.').');
		}
		if (!($entity instanceof VotableParentInterface)) {
			throw $this->createNotFoundException('Parent Entity must implements VotableParentInterface.');
		}

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $voteRepository->findPaginedByVotableParent($entity, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_vote_list_parent_entity_filter_page', array( 'entityType' => $entityType, 'entityId' => $entityId, 'filter' => $filter ), $page, $entity->getVoteCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Vote:list-byparent-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'entity'   => $entity,
			'authored' => $entity instanceof AuthoredInterface,
		));
	}

}