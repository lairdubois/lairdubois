<?php

namespace App\Controller\Knowledge;

use App\Entity\Knowledge\Value\BaseValue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Controller\AbstractController;
use App\Entity\Core\User;
use App\Entity\Knowledge\AbstractKnowledge;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;
use App\Model\WatchableInterface;
use App\Utils\KnowledgeUtils;
use App\Utils\VotableUtils;
use App\Utils\CommentableUtils;
use App\Utils\WatchableUtils;
use App\Utils\PaginatorUtils;
use App\Utils\SearchUtils;
use App\Utils\TypableUtils;
use App\Utils\ActivityUtils;
use App\Utils\PropertyUtils;

/**
 * @Route("/knowledge")
 */
class KnowledgeController extends AbstractController {

	private function _retrieveRelatedEntityRepository($entityType) {

		$typableUtils = $this->get(TypableUtils::class);
		try {
			$entityRepository = $typableUtils->getRepositoryByType($entityType);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}

		return $entityRepository;
	}

	private function _retrieveRelatedEntity($entityRepository, $entityId) {

		$entity = $entityRepository->findOneById($entityId);
		if (is_null($entity)) {
			throw $this->createNotFoundException('Unknow Entity (entityId='.$entityId.').');
		}
		if (!($entity instanceof AbstractKnowledge)) {
			throw $this->createNotFoundException('Entity must extends AbstractKnowledge.');
		}

		return $entity;
	}

	private function _retieveFieldDef($entity, $field) {

		$fieldDef = $entity->getFieldDefs()[$field];
		if (is_null($fieldDef)) {
			throw $this->createNotFoundException('Unknow knowledge field (field='.$field.').');
		}

		return $fieldDef;
	}

	private function _getFieldType($fieldDef) {
		return $fieldDef[AbstractKnowledge::ATTRIB_TYPE];
	}

	private function _getFieldMutltiple($fieldDef) {
		return $fieldDef[AbstractKnowledge::ATTRIB_MULTIPLE];
	}

	private function _getFieldChoices($fieldDef) {
		return isset($fieldDef[AbstractKnowledge::ATTRIB_CHOICES]) ? $fieldDef[AbstractKnowledge::ATTRIB_CHOICES] : null;
	}

	private function _getFieldConstraints($fieldDef, $entity, $attib = AbstractKnowledge::ATTRIB_CONSTRAINTS) {
		$constraintDefs = isset($fieldDef[$attib]) ? $fieldDef[$attib] : null;
		$fieldConstraints = array();
		if (!is_null($constraintDefs)) {
			foreach ($constraintDefs as $constraintDef) {
				$constraint = new $constraintDef[0]();
				if (isset($constraintDef[1])) {
					foreach ($constraintDef[1] as $key => $value) {
						if (is_string($value) && strpos($value, '@') !== false) {
							$value = $entity->{substr($value, 1)}();
						}
						$constraint->{$key} = $value;
					}
				}
				$fieldConstraints[] = $constraint;
			}
		}
		return $fieldConstraints;
	}

	private function _computeEntityClass($fieldType) {
		return 'App\\Entity\\Knowledge\\Value\\'.implode('', array_map('ucfirst', explode('-', $fieldType)));
	}

	private function _computeFormTypeFqcn($fieldType) {
		return 'Ladb\\CoreBundle\\Form\\Type\\Knowledge\\Value\\'.implode('', array_map('ucfirst', explode('-', $fieldType))).'ValueType';
	}

	private function _retrieveValue($valueRepository, $id) {
		$value = $valueRepository->findOneById($id);
		if (is_null($value)) {
			throw $this->createNotFoundException('Unable to find Value entity (id='.$id.').');
		}

		return $value;
	}

	private function _computeFormTypeName($fieldType, $value) {
		return 'ladb_knowledge_value_'.implode('_', explode('-', $fieldType)).'_'.$value->getId();
	}

	/////

	/**
	 * @Route("/{entityType}/{entityId}/contributors", requirements={"entityType" = "\d+","entityId" = "\d+"}, name="core_knowledge_contributors")
	 * @Route("/{entityType}/{entityId}/contributors/{page}", requirements={"entityType" = "\d+", "entityId" = "\d+", "page" = "\d+"}, name="core_knowledge_contributors_page")
	 * @Template("Knowledge/contributors.html.twig")
	 */
	public function contributors(Request $request, $entityType, $entityId, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Retrive contributors

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$contributorIds = array_slice($entityRepository->findUserIdsById($entity->getId()), $offset, $limit - $offset);
		$contributors = $userRepository->findByIds($contributorIds);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_knowledge_contributors_page', array('entityType' => $entityType, 'entityId' => $entityId), $page, $entity->getContributorCount());

		$parameters = array(
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'entity'      => $entity,
			'users'       => $contributors,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/contributors-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/create", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+"}, methods={"POST"}, name="core_knowledge_value_create")
	 * @Template("Knowledge/value-new-xhr.html.twig")
	 */
	public function createFieldValue(Request $request, $entityType, $entityId, $field) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_create)');
		}

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_knowledge_value_create)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);
		$propertyUtils = $this->get(PropertyUtils::class);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$value = new $entityClass();
		$value->setParentEntity($entity);
		$value->setParentEntityField($field);    // Before form validation because it was used for the uniqueness
		$form = $this->createForm($formTypeFqcn, $value, array('choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints));
		$form->handleRequest($request);
		if ($form->isValid()) {

			if ($value->getModerationScore() != 0 && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				throw $this->createNotFoundException('Not allowed - moderationScore (core_knowledge_value_create)');
			}

			$user = $this->getUser();

			$value->setCreatedAt(new \DateTime());    // Force createdAt to be able to sort values on this field
			$value->setUser($user);
			$user->getMeta()->incrementProposalCount();

			$propertyUtils->addValue($entity, $field.'_value', $value);

			// Dispatch knowledge event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new KnowledgeEvent($entity, array('field' => $field, 'value' => $value)), KnowledgeListener::FIELD_VALUE_ADDED);

			// Update contributors
			$contributorIds = $entityRepository->findUserIdsById($entity->getId());
			$newContributor = !in_array($user->getId(), $contributorIds);
			if ($newContributor) {
				$entity->incrementContributorCount();
			}

			$om->persist($value);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($value, false);

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_CHANGED);

			if ($entity instanceof WatchableInterface) {
				$watchableUtils = $this->get(WatchableUtils::class);

				// Auto watch
				$watchableUtils->autoCreateWatch($entity, $user);

			}

			// Regenerate en empty form
			$value = new $entityClass();
			$form = $this->createForm($formTypeFqcn, $value, array('choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints));
			$values = $propertyUtils->getValue($entity, $field.'_values');

			$commentableUtils = $this->get(CommentableUtils::class);
			$votableUtils = $this->get(VotableUtils::class);

			return $this->render('Knowledge/value-create-xhr.html.twig', array(
				'knowledge'       => $entity,
				'field'           => $field,
				'form'            => $form->createView(),
				'sourcesHistory'  => $knowledgeUtils->getValueSourcesHistory(),
				'values'          => $values,
				'commentContexts' => $commentableUtils->getCommentContexts($values),
				'voteContexts'    => $votableUtils->getVoteContexts($values, $user),
			));

		}

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/edit", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+","id" = "\d+"}, name="core_knowledge_value_edit")
	 * @Template("Knowledge/value-edit-xhr.html.twig")
	 */
	public function editFieldValue(Request $request, $entityType, $entityId, $field, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_edit)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_edit)');
		}

		$formTypeName = $this->_computeFormTypeName($fieldType, $value);

		$form = $this->get('form.factory')->createNamed($formTypeName, $formTypeFqcn, $value, array('choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints));

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/update", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+","id" = "\d+"}, name="core_knowledge_value_update")
	 * @Template("Knowledge/value-edit-xhr.html.twig")
	 */
	public function updateFieldValue(Request $request, $entityType, $entityId, $field, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_update)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);
		$fieldDataConstraints = $this->_getFieldConstraints($fieldDef, $entity, AbstractKnowledge::ATTRIB_DATA_CONSTRAINTS);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_update)');
		}

		$previousMederationScore = $value->getModerationScore();

		$formTypeName = $this->_computeFormTypeName($fieldType, $value);

		$form = $this->get('form.factory')->createNamed($formTypeName, $formTypeFqcn, $value, array('choices' => $fieldChoices, 'dataConstraints' => $fieldDataConstraints, 'constraints' => $fieldConstraints));
		$form->handleRequest($request);
		if ($form->isValid()) {

			if ($value->getModerationScore() != $previousMederationScore && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				throw $this->createNotFoundException('Not allowed - moderationScore (core_knowledge_value_update)');
			}

			// Dispatch knowledge event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new KnowledgeEvent($entity, array('field' => $field, 'value' => $value)), KnowledgeListener::FIELD_VALUE_UPDATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_UPDATED);

			$commentableUtils = $this->get(CommentableUtils::class);
			$votableUtils = $this->get(VotableUtils::class);

			return $this->render('Knowledge/value-update-xhr.html.twig', array(
				'knowledge'      => $entity,
				'field'          => $field,
				'value'          => $value,
				'commentContext' => $commentableUtils->getCommentContext($value),
				'voteContext'    => $votableUtils->getVoteContext($value, $this->getUser()),
			));

		}

		return array(
			'knowledge'      => $entity,
			'field'          => $field,
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}/{id}/delete", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "\w+", "id" = "\d+"}, name="core_knowledge_value_delete")
	 * @Template("Knowledge/value-delete-xhr.html.twig")
	 */
	public function deleteFieldValue(Request $request, $entityType, $entityId, $field, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_delete)');
		}

		$propertyUtils = $this->get(PropertyUtils::class);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $fieldDef[AbstractKnowledge::ATTRIB_TYPE];
		$fieldMandatory = isset($fieldDef[AbstractKnowledge::ATTRIB_MANDATORY]) && $fieldDef[AbstractKnowledge::ATTRIB_MANDATORY];

		$entityClass = $this->_computeEntityClass($fieldType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && ($value->getUser()->getId() != $this->getUser()->getId() || $value->getVoteCount() > 0 || $fieldMandatory)) {
			throw $this->createNotFoundException('Not allowed (core_knowledge_value_delete)');
		}

		$propertyUtils->removeValue($entity, $field.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new KnowledgeEvent($entity, array('field' => $field, 'value' => $value)), KnowledgeListener::FIELD_VALUE_REMOVED);

		// Decrement user proposal count
		$value->getUser()->getMeta()->incrementProposalCount(-1);

		// Delete comments
		$commentableUtils = $this->get(CommentableUtils::class);
		$commentableUtils->deleteComments($value, false);

		// Delete votes
		$votableUtils = $this->get(VotableUtils::class);
		$votableUtils->deleteVotes($value, $entity, false);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByValue($value, false);

		$om->remove($value);
		$om->flush(); // Flushed to be sure the deleted value will be excluded from contributor request

		// Update contributors
		$contributorIds = $entityRepository->findUserIdsById($entity->getId());
		$noMoreContribution = !in_array($value->getUser()->getId(), $contributorIds);
		if ($noMoreContribution) {
			$entity->incrementContributorCount(-1);
			$om->flush();    // Flush updated entity
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_CHANGED);

		// Reload values

		$values = $propertyUtils->getValue($entity, $field.'_values');

		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);

		return array(
			'knowledge'       => $entity,
			'field'           => $field,
			'values'          => $values,
			'commentContexts' => $commentableUtils->getCommentContexts($values),
			'voteContexts'    => $votableUtils->getVoteContexts($values, $this->getUser()),
		);
	}

	/**
	 * @Route("/{entityType}/{entityId}/{field}.xhr", requirements={"entityType" = "\d+","entityId" = "\d+", "field" = "[a-z_]+"}, name="core_knowledge_field_show")
	 * @Template("Knowledge/field-show.html.twig")
	 */
	public function showField(Request $request, $entityType, $entityId, $field, $highlightedValueId = null) {
		$knowledgeUtils = $this->get(KnowledgeUtils::class);
		$propertyUtils = $this->get(PropertyUtils::class);

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldDef = $this->_retieveFieldDef($entity, $field);

		$fieldType = $this->_getFieldType($fieldDef);
		$fieldChoices = $this->_getFieldChoices($fieldDef);
		$fieldConstraints = $this->_getFieldConstraints($fieldDef, $entity);

		$entityClass = $this->_computeEntityClass($fieldType);
		$formTypeFqcn = $this->_computeFormTypeFqcn($fieldType);

		$values = $propertyUtils->getValue($entity, $field.'_values');
		$value = new $entityClass();

		$form = null;
		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getEmailConfirmed()) {
			$form = $this->createForm($formTypeFqcn, $value, array('choices' => $fieldChoices, 'dataConstraints' => $fieldConstraints));
		}

		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);

		$parameters = array(
			'knowledge'          => $entity,
			'field'              => $field,
			'values'             => $values,
			'commentContexts'    => $commentableUtils->getCommentContexts($values),
			'voteContexts'       => $votableUtils->getVoteContexts($values, $this->getUser()),
			'form'               => is_null($form) ? null : $form->createView(),
			'sourcesHistory'     => $knowledgeUtils->getValueSourcesHistory(),
			'highlightedValueId' => $highlightedValueId,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/field-show-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/value/{id}", requirements={"id" = "\d+"}, name="core_knowledge_value_show")
	 */
	public function valueShow(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$valueRepository = $om->getRepository(BaseValue::CLASS_NAME);

		$value = $valueRepository->findOneById($id);
		if (is_null($value)) {
			throw $this->createNotFoundException('Unable to find Value entity (id='.$id.').');
		}

		return $this->forward('App\Entity\Knowledge/Knowledge/showField', array(
			'entityType'         => $value->getParentEntityType(),
			'entityId'           => $value->getParentEntityId(),
			'field'              => $value->getParentEntityField(),
			'highlightedValueId' => $value->getId(),
		));
	}

	/**
	 * @Route("/{entityType}/{entityId}/{fieldSrc}/{fieldDest}/{id}/admin/move", requirements={"entityType" = "\d+","entityId" = "\d+", "fieldSrc" = "\w+", "fieldDest" = "\w+", "id" = "\d+"}, name="core_knowledge_value_admin_move")
	 * @Template("Knowledge/value-move-xhr.html.twig")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_knowledge_value_admin_move)")
	 */
	public function adminMoveFieldValue(Request $request, $entityType, $entityId, $fieldSrc, $fieldDest, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_knowledge_value_admin_move)');
		}
		if ($fieldSrc == $fieldDest) {
			throw $this->createNotFoundException('Can\'t move to the same field (core_knowledge_value_admin_move)');
		}

		$propertyUtils = $this->get(PropertyUtils::class);
		$om = $this->getDoctrine()->getManager();

		// Retrieve related entity

		$entityRepository = $this->_retrieveRelatedEntityRepository($entityType);
		$entity = $this->_retrieveRelatedEntity($entityRepository, $entityId);

		// Process field

		$fieldSrcDef = $this->_retieveFieldDef($entity, $fieldSrc);
		$fieldDestDef = $this->_retieveFieldDef($entity, $fieldDest);

		$fieldSrcType = $fieldSrcDef[AbstractKnowledge::ATTRIB_TYPE];
		$fieldDestType = $fieldDestDef[AbstractKnowledge::ATTRIB_TYPE];

		if ($fieldSrcType != $fieldDestType) {
			throw $this->createNotFoundException('Incompatible field types (core_knowledge_value_admin_move)');
		}

		$entityClass = $this->_computeEntityClass($fieldSrcType);

		$valueRepository = $om->getRepository($entityClass::CLASS_NAME);
		$value = $this->_retrieveValue($valueRepository, $id);

		$value->setParentEntityField($fieldDest);

		// Remove from SRC
		$propertyUtils->removeValue($entity, $fieldSrc.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new KnowledgeEvent($entity, array('field' => $fieldSrc, 'value' => $value)), KnowledgeListener::FIELD_VALUE_REMOVED);

		// Add to DEST
		$propertyUtils->addValue($entity, $fieldDest.'_value', $value);

		// Dispatch knowledge event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new KnowledgeEvent($entity, array('field' => $fieldDest, 'value' => $value)), KnowledgeListener::FIELD_VALUE_ADDED);

		$om->flush();

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($entity), PublicationListener::PUBLICATION_CHANGED);

		// Reload values

		$values = $propertyUtils->getValue($entity, $fieldSrc.'_values');

		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);

		return array(
			'knowledge'       => $entity,
			'field'           => $fieldSrc,
			'values'          => $values,
			'commentContexts' => $commentableUtils->getCommentContexts($values),
			'voteContexts'    => $votableUtils->getVoteContexts($values, $this->getUser()),
		);
	}

}
