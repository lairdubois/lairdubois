<?php

namespace App\Controller\Howto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Controller\PublicationControllerTrait;
use App\Entity\Knowledge\School;
use App\Entity\Qa\Question;
use App\Entity\Workflow\Workflow;
use App\Model\HiddableInterface;
use App\Utils\StripableUtils;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Entity\Howto\Howto;
use App\Entity\Knowledge\Provider;
use App\Form\Type\Howto\HowtoType;
use App\Utils\PaginatorUtils;
use App\Utils\TagUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\SearchUtils;
use App\Utils\EmbeddableUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Manager\Howto\HowtoManager;
use App\Manager\Core\WitnessManager;

/**
 * @Route("/pas-a-pas")
 */
class HowtoController extends AbstractHowtoBasedController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_howto_new")
	 * @Template("Howto/Howto:new.html.twig")
	 */
	public function new(Request $request) {

		$howto = new Howto();
		$form = $this->createForm(HowtoType::class, $howto);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'owner'        => $this->retrieveOwner($request),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_howto_create")
	 * @Template("Howto/Howto:new.html.twig")
	 */
	public function create(Request $request) {

		$owner = $this->retrieveOwner($request);

		$this->createLock('core_howto_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$howto = new Howto();
		$form = $this->createForm(HowtoType::class, $howto);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($howto);

			$howto->setUser($owner);
			$owner->getMeta()->incrementPrivateHowtoCount();

			$om->persist($howto);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($howto), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_howto_article_new', array( 'id' => $howto->getId(), 'owner' => $owner->getUsernameCanonical() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'owner'		   => $owner,
			'tagProposals' => $tagUtils->getProposals($howto),
			'hideWarning'  => true,
		);
	}

	/////

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_howto_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_howto_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertLockUnlockable($howto, $lock);

		// Lock or Unlock
		$howtoManager = $this->get(HowtoManager::class);
		if ($lock) {
			$howtoManager->lock($howto);
		} else {
			$howtoManager->unlock($howto);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_howto_publish")
	 */
	public function publish($id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertPublishable($howto);

		// Publish
		$howtoManager = $this->get(HowtoManager::class);
		$howtoManager->publish($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.publish_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_howto_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertUnpublishable($howto);

		// Unpublish
		$howtoManager = $this->get(HowtoManager::class);
		$howtoManager->unpublish($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.unpublish_success', array( '%title%' => $howto->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_howto_edit")
	 * @Template("Howto/Howto:edit.html.twig")
	 */
	public function edit($id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertEditabable($howto);

		$form = $this->createForm(HowtoType::class, $howto);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_howto_update")
	 * @Template("Howto/Howto:edit.html.twig")
	 */
	public function update(Request $request, $id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertEditabable($howto);

		$howto->resetArticles();	// Reset articles array to consider form articles order

		$previouslyUsedTags = $howto->getTags()->toArray();	// Need to be an array to copy values

		$form = $this->createForm(HowtoType::class, $howto);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($howto);

			$embaddableUtils = $this->get(EmbeddableUtils::class);
			$embaddableUtils->resetSticker($howto);

			if ($howto->getUser() == $this->getUser()) {
				$howto->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($howto, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.update_success', array( '%title%' => $howto->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(HowtoType::class, $howto);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'howto'        => $howto,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($howto),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_howto_delete")
	 */
	public function delete($id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertDeletable($howto);

		// Delete
		$howtoManager = $this->get(HowtoManager::class);
		$howtoManager->delete($howto);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.delete_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_list'));
    }

	/**
	 * @Route("/{id}/chown", requirements={"id" = "\d+"}, name="core_howto_chown")
	 */
	public function chown(Request $request, $id) {

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertChownable($howto);

		$targetUser = $this->retrieveOwner($request);

		// Change owner
		$howtoManager = $this->get(HowtoManager::class);
		$howtoManager->changeOwner($howto, $targetUser);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('howto.howto.form.alert.chown_success', array( '%title%' => $howto->getTitle() )));

		return $this->redirect($this->generateUrl('core_howto_show', array( 'id' => $howto->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/sticker.png", requirements={"id" = "\d+"}, name="core_howto_sticker_png")
	 * @Route("/{id}/sticker", requirements={"id" = "\d+"}, name="core_howto_sticker")
	 */
	public function sticker(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$id = intval($id);

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto, true);

		$sticker = $howto->getSticker();
		if (is_null($sticker)) {
			$embeddableUtils = $this->get(EmbeddableUtils::class);
			$sticker = $embeddableUtils->generateSticker($howto);
			if (!is_null($sticker)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating sticker (core_howto_sticker)');
			}
		}

		if (!is_null($sticker)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $sticker->getWebPath(), '598w');
			return $response;

		} else {
			throw $this->createNotFoundException('No sticker');
		}

	}

	/**
	 * @Route("/{id}/strip", requirements={"id" = "\d+"}, name="core_howto_strip")
	 */
	public function strip(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();

		$id = intval($id);

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto, true);

		$strip = $howto->getStrip();
		if (is_null($strip)) {
			$stripableUtils = $this->get(StripableUtils::class);
			$strip = $stripableUtils->generateStrip($howto);
			if (!is_null($strip)) {
				$om->flush();
			} else {
				throw $this->createNotFoundException('Error creating strip (core_howto_strip)');
			}
		}

		if (!is_null($strip)) {

			$response = $this->get('liip_imagine.controller')->filterAction($request, $strip->getWebPath(), '564w');
			return $response;

		} else {
			throw $this->createNotFoundException('No strip');
		}

	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_howto_widget")
	 * @Template("Howto/Howto:widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$id = intval($id);

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto, true);

		return array(
			'howto' => $howto,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_howto_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_howto_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_howto_list_page")
	 * @Template("Howto/Howto:list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_howto_list_page)');
		}

		$layout = $request->get('layout', 'view');

		$routeParameters = array();
		if ($layout != 'view') {
			$routeParameters['layout'] = $layout;
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addFilter(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addFilter(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

						}

						break;

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-30d/d' ));

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					case 'author':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'user.displayname', 'user.fullname', 'user.username'  ));
						$filters[] = $filter;

						break;

					case 'kind':

						$filter = new \Elastica\Query\MatchPhrase('kind', $facet->value);
						$filters[] = $filter;

						break;

					case 'license':

						$filter = new \Elastica\Query\Term([ 'license.strippedname' => [ 'value' => $facet->value, 'boost' => 1.0 ] ]);
						$filters[] = $filter;

						break;

					case 'content-questions':

						$filter = new \Elastica\Query\Range('questionCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-creations':

						$filter = new \Elastica\Query\Range('creationCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-plans':

						$filter = new \Elastica\Query\Range('planCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-workshops':

						$filter = new \Elastica\Query\Range('workshopCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-workflows':

						$filter = new \Elastica\Query\Range('workflowCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-providers':

						$filter = new \Elastica\Query\Range('providerCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-schools':

						$filter = new \Elastica\Query\Range('schoolCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'content-videos':

						$filter = new \Elastica\Query\Range('bodyBlockVideoCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'wip':

						$filter = new \Elastica\Query\Range('isWorkInProgress', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'with-spotlight':

						$filter = new \Elastica\Query\Range('withEnabledSpotlight', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body', 'articles.title^50', 'articles.body', 'tags.label' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) use ($layout) {

				$this->pushGlobalVisibilityFilter($filters, $layout != 'choice', true);

			},
			'howto_howto',
			\App\Entity\Howto\Howto::CLASS_NAME,
			'core_howto_list_page',
			$routeParameters
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'howtos'          => $searchParameters['entities'],
			'layout'          => $layout,
			'routeParameters' => $routeParameters,
		));

		if ($request->isXmlHttpRequest()) {
			if ($layout == 'choice') {
				return $this->render('Howto/Howto:list-choice-xhr.html.twig', $parameters);
			} else {
				return $this->render('Howto/Howto:list-xhr.html.twig', $parameters);
			}
		}

		if ($layout == 'choice') {
			return $this->render('Howto/Howto:list-choice.html.twig', $parameters);
		}

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getPrivateHowtoCount() > 0) {

			$draftPath = $this->generateUrl('core_howto_list', array( 'q' => '@mine:draft' ));
			$draftCount = $this->getUser()->getMeta()->getPrivateHowtoCount();

			// Flashbag
			$this->get('session')->getFlashBag()->add('info', '<i class="ladb-icon-warning"></i> '.$this->get('translator')->trans('howto.howto.choice.draft_alert', array( 'count' => $draftCount )).' <small><a href="'.$draftPath.'" class="alert-link">('.$this->get('translator')->trans('default.show_my_drafts').')</a></small>');

		}

		return $parameters;
	}

	/**
	 * @Route("/{id}/creations", requirements={"id" = "\d+"}, name="core_howto_creations")
	 * @Route("/{id}/creations/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_creations_filter")
	 * @Route("/{id}/creations/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_creations_filter_page")
	 * @Template("Howto/Howto:creations.html.twig")
	 */
	public function creations(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Creations

		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_creations_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
    }

	/**
	 * @Route("/{id}/ateliers", requirements={"id" = "\d+"}, name="core_howto_workshops")
	 * @Route("/{id}/ateliers/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_workshops_filter")
	 * @Route("/{id}/ateliers/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_workshops_filter_page")
	 * @Template("Howto/Howto:workshops.html.twig")
	 */
	public function workshops(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Workshops

		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workshopRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_workshops_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workshops'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Workshop:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}/plans", requirements={"id" = "\d+"}, name="core_howto_plans")
	 * @Route("/{id}/plans/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_plans_filter")
	 * @Route("/{id}/plans/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_plans_filter_page")
	 * @Template("Howto/Howto:plans.html.twig")
	 */
	public function plans(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Plans

		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_plans_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}/questions", requirements={"id" = "\d+"}, name="core_howto_questions")
	 * @Route("/{id}/questions/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_questions_filter")
	 * @Route("/{id}/questions/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_questions_filter_page")
	 * @Template("Howto/Howto:questions.html.twig")
	 */
	public function questions(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Plans

		$questionRepository = $om->getRepository(Question::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $questionRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_questions_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'questions'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}/processus", requirements={"id" = "\d+"}, name="core_howto_workflows")
	 * @Route("/{id}/processus/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_workflows_filter")
	 * @Route("/{id}/processus/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_workflows_filter_page")
	 * @Template("Howto/Howto:workflows.html.twig")
	 */
	public function workflows(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Workflows

		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_workflows_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}/fournisseurs", requirements={"id" = "\d+"}, name="core_howto_providers")
	 * @Route("/{id}/fournisseurs/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_providers_filter")
	 * @Route("/{id}/fournisseurs/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_providers_filter_page")
	 * @Template("Howto/Howto:providers.html.twig")
	 */
	public function providers(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Providers

		$providerRepository = $om->getRepository(Provider::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $providerRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_providers_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'providers'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}/ecoles", requirements={"id" = "\d+"}, name="core_howto_schools")
	 * @Route("/{id}/ecoles/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_howto_schools_filter")
	 * @Route("/{id}/ecoles/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_howto_schools_filter_page")
	 * @Template("Howto/Howto:schools.html.twig")
	 */
	public function schools(Request $request, $id, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();

		$howto = $this->retrievePublication($id, Howto::CLASS_NAME);
		$this->assertShowable($howto);

		// Schools

		$schoolRepository = $om->getRepository(School::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $schoolRepository->findPaginedByHowto($howto, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_howto_schools_filter_page', array( 'id' => $id, 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'schools'     => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return array_merge($parameters, array(
			'howto' => $howto,
		));
	}

	/**
	 * @Route("/{id}.html", name="core_howto_show")
	 * @Template("Howto/Howto:show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$howto = $howtoRepository->findOneByIdJoinedOnOptimized($id);
        if (is_null($howto)) {
			if ($response = $witnessManager->checkResponse(Howto::TYPE, $id)) {
				return $response;
			}
            throw $this->createNotFoundException('Unable to find Howto entity (id='.$id.').');
        }
		$this->assertShowable($howto);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($howto), PublicationListener::PUBLICATION_SHOWN);

		return $this->computeShowParameters($howto, $request);
	}

}