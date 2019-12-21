<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Core\TipManager;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Entity\Core\Tip;
use Ladb\CoreBundle\Form\Type\Core\TipType;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;

/**
 * @Route("/le-saviez-vous")
 */
class TipController extends Controller {

	/**
	 * @Route("/new", name="core_tip_new")
	 * @Template("LadbCoreBundle:Core/Tip:new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_new)")
	 */
	public function newAction() {

		$tip = new Tip();
		$form = $this->createForm(TipType::class, $tip);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_tip_create")
	 * @Template("LadbCoreBundle:Core/Tip:new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_create)")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$tip = new Tip();
		$form = $this->createForm(TipType::class, $tip);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($tip);

			$om->persist($tip);
			$om->flush();

			$om->flush();	// Resave to store file size

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($tip));

			return $this->redirect($this->generateUrl('core_tip_show', array('id' => $tip->getId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'tip'         => $tip,
			'form'         => $form->createView(),
			'hideWarning'  => true,
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_tip_edit")
	 * @Template("LadbCoreBundle:Core/Tip:edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_edit)")
	 */
	public function editAction($id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::CLASS_NAME);

		$tip = $tipRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($tip)) {
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		$form = $this->createForm(TipType::class, $tip);

		return array(
			'tip'  => $tip,
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_tip_update")
	 * @Template("LadbCoreBundle:Core/Tip:edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_update)")
	 */
	public function updateAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::CLASS_NAME);

		$tip = $tipRepository->findOneByIdJoinedOnUser($id);
		if (is_null($tip)) {
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		$form = $this->createForm(TipType::class, $tip);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($tip);

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($tip));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('core.tip.form.alert.update_success', array( '%title%' => $tip->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(TipType::class, $tip);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		return array(
			'tip'  => $tip,
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_tip_delete")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_delete)")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::CLASS_NAME);

		$tip = $tipRepository->findOneByIdJoinedOnUser($id);
		if (is_null($tip)) {
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		// Delete
		$tipManager = $this->get(TipManager::NAME);
		$tipManager->delete($tip);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('core.tip.form.alert.delete_success', array( '%title%' => $tip->getTitle() )));

		return $this->redirect($this->generateUrl('core_tip_list'));
	}

	/**
	 * @Route("/", name="core_tip_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_tip_list_page")
	 * @Template("LadbCoreBundle:Core/Tip:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_tip_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
				switch ($facet->name) {

					// Filters /////

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					case 'popular-downloads':
						$sort = array( 'downloadCount' => array( 'order' => 'desc' ) );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user)) {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addFilter(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addFilter(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.tip',
			\Ladb\CoreBundle\Entity\Core\Tip::CLASS_NAME,
			'core_tip_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'tips' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Tip:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}", name="core_tip_show")
	 * @Template("LadbCoreBundle:Core/Tip:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$tip = $tipRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($tip)) {
			if ($response = $witnessManager->checkResponse(Tip::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($tip));

		return array(
			'tip' => $tip,
		);
	}

}