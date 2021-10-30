<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use App\Entity\Core\Tip;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Core\TipType;
use App\Manager\Core\TipManager;
use App\Manager\Core\WitnessManager;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\SearchUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/astuces")
 */
class TipController extends AbstractController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.TipManager::class,
            '?'.WitnessManager::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.SearchUtils::class,
        ));
    }

    /**
	 * @Route("/new", name="core_tip_new")
	 * @Template("Core/Tip/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_new)")
	 */
	public function new() {

		$tip = new Tip();
		$form = $this->createForm(TipType::class, $tip);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_tip_create")
	 * @Template("Core/Tip/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_create)")
	 */
	public function create(Request $request) {

		$this->createLock('core_tip_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$tip = new Tip();
		$form = $this->createForm(TipType::class, $tip);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($tip);

			$om->persist($tip);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($tip), PublicationListener::PUBLICATION_CREATED);
			$dispatcher->dispatch(new PublicationEvent($tip), PublicationListener::PUBLICATION_PUBLISHED);

			return $this->redirect($this->generateUrl('core_tip_list'));
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
	 * @Template("Core/Tip/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_edit)")
	 */
	public function edit($id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::class);

		$tip = $tipRepository->findOneById($id);
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
	 * @Template("Core/Tip/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_TIP')", statusCode=404, message="Not allowed (core_tip_update)")
	 */
	public function update(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::class);

		$tip = $tipRepository->findOneById($id);
		if (is_null($tip)) {
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		$form = $this->createForm(TipType::class, $tip);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($tip);

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($tip), PublicationListener::PUBLICATION_UPDATED);

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
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::class);

		$tip = $tipRepository->findOneById($id);
		if (is_null($tip)) {
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		// Delete
		$tipManager = $this->get(TipManager::class);
		$tipManager->delete($tip);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('core.tip.form.alert.delete_success', array( '%title%' => $tip->getTitle() )));

		return $this->redirect($this->generateUrl('core_tip_list'));
	}

	/**
	 * @Route("/", name="core_tip_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_tip_list_page")
	 * @Template("Core/Tip/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

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

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'body' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			null,
			'core_tip',
			\App\Entity\Core\Tip::class,
			'core_tip_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], false), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'tips' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Tip/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_tip_show")
	 * @Template("Core/Tip/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$tipRepository = $om->getRepository(Tip::class);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$tip = $tipRepository->findOneById($id);
		if (is_null($tip)) {
			if ($response = $witnessManager->checkResponse(Tip::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Tip entity (id='.$id.').');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($tip), PublicationListener::PUBLICATION_SHOWN);

		return $this->redirect($tip->getUrl());
	}

}