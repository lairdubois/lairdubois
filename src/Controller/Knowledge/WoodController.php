<?php

namespace App\Controller\Knowledge;

use App\Controller\PublicationControllerTrait;
use App\Entity\Knowledge\Value\Picture;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Controller\AbstractController;
use App\Form\Type\Knowledge\NewWoodType;
use App\Form\Model\NewWood;
use App\Entity\Knowledge\Wood;
use App\Entity\Knowledge\Value\Text;
use App\Utils\CollectionnableUtils;
use App\Utils\KnowledgeUtils;
use App\Utils\PaginatorUtils;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\TextureUtils;
use App\Utils\ElasticaQueryUtils;
use App\Utils\ActivityUtils;
use App\Manager\Knowledge\WoodManager;
use App\Manager\Core\WitnessManager;
use App\Event\PublicationsEvent;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;

/**
 * @Route("/xylotheque")
 */
class WoodController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_wood_new")
	 * @Template("Knowledge/Wood:new.html.twig")
	 */
	public function new() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_wood_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$newWood = new NewWood();
		$form = $this->createForm(NewWoodType::class, $newWood);

		return array(
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_wood_create")
	 * @Template("Knowledge/Wood:new.html.twig")
	 */
	public function create(Request $request) {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_wood_create)');
		}

		$this->createLock('core_wood_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newWood = new NewWood();
		$form = $this->createForm(NewWoodType::class, $newWood);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$nameValue = $newWood->getNameValue();
			$grainValue = $newWood->getGrainValue();
			$user = $this->getUser();

			// Sanitize Name values
			if ($nameValue instanceof Text) {
				$nameValue->setData(trim(ucfirst($nameValue->getData())));
			}

			$wood = new Wood();
			$wood->setName($nameValue->getData());
			$wood->incrementContributorCount();

			$om->persist($wood);
			$om->flush();	// Need to save wood to be sure ID is generated

			$wood->addNameValue($nameValue);
			$wood->addGrainValue($grainValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(new KnowledgeEvent($wood, array( 'field' => Wood::FIELD_NAME, 'value' => $nameValue )), KnowledgeListener::FIELD_VALUE_ADDED);
			$dispatcher->dispatch(new KnowledgeEvent($wood, array( 'field' => Wood::FIELD_GRAIN, 'value' => $grainValue )), KnowledgeListener::FIELD_VALUE_ADDED);

			$nameValue->setParentEntity($wood);
			$nameValue->setParentEntityField(Wood::FIELD_NAME);
			$nameValue->setUser($user);

			$grainValue->setParentEntity($wood);
			$grainValue->setParentEntityField(Wood::FIELD_GRAIN);
			$grainValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new wood

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($nameValue, false);
			$activityUtils->createContributeActivity($grainValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($wood), PublicationListener::PUBLICATION_CREATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($wood), PublicationListener::PUBLICATION_PUBLISHED);

			return $this->redirect($this->generateUrl('core_wood_show', array('id' => $wood->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'newWood'     => $newWood,
			'form'        => $form->createView(),
			'hideWarning' => true,
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_wood_delete")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_wood_delete)")
	 */
	public function delete($id) {

		$wood = $this->retrievePublication($id, Wood::CLASS_NAME);
		$this->assertDeletable($wood);

		// Delete
		$woodMananger = $this->get(WoodManager::class);
		$woodMananger->delete($wood);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.wood.form.alert.delete_success', array( '%title%' => $wood->getTitle() )));

		return $this->redirect($this->generateUrl('core_wood_list'));
	}

	/**
	 * @Route("/{id}/textures", requirements={"id" = "\d+"}, name="core_wood_texture_list")
	 * @Route("/{id}/textures/{filter}", requirements={"id" = "\d+", "filter" = "[a-z-]+"}, name="core_wood_texture_list_filter")
	 * @Route("/{id}/textures/{filter}/{page}", requirements={"id" = "\d+", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_wood_texture_list_filter_page")
	 * @Template("Knowledge/Wood:texture-list.html.twig")
	 */
	public function textureList(Request $request, $id, $page = 0, $filter = 'all') {
		$om = $this->getDoctrine()->getManager();
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$wood = $this->retrievePublication($id, Wood::CLASS_NAME);
		$this->assertShowable($wood);

		$textureRepository = $om->getRepository(Wood\Texture::CLASS_NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $textureRepository->findPaginedByWood($wood, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_wood_texture_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'wood'        => $wood,
			'textures'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/Wood:texture-list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/textures/{id}/download", requirements={"id" = "\d+"}, name="core_wood_texture_download")
	 */
	public function textureDownload($id) {
		$om = $this->getDoctrine()->getManager();
		$textureRepository = $om->getRepository(Wood\Texture::CLASS_NAME);

		$texture = $textureRepository->findOneById($id);
		if (is_null($texture)) {
			throw $this->createNotFoundException('Unable to find Texture entity (id='.$id.').');
		}

		$textureUtils = $this->get(TextureUtils::class);
		$zipAbsolutePath = $textureUtils->getZipAbsolutePath($texture);
		if (!file_exists($zipAbsolutePath)) {
			if (!$textureUtils->createZipArchive($texture)) {
				throw $this->createNotFoundException('Zip archive not found (core_wood_texture_download)');
			}
		}

		$texture->incrementDownloadCount(1);

		$om->flush();

		$content = file_get_contents($zipAbsolutePath);

		$response = new Response();
		$response->headers->set('Content-Type', 'mime/type');
		$response->headers->set('Content-Length', filesize($zipAbsolutePath));
		$response->headers->set('Content-Disposition', 'attachment;filename="lairdubois_texture_'.$textureUtils->getBaseFilename($texture).'.zip"');
		$response->headers->set('Expires', 0);
		$response->headers->set('Cache-Control', 'no-cache, must-revalidate');
		$response->headers->set('Pragma', 'no-cache');

		$response->setContent($content);

		return $response;
	}

	/**
	 * @Route("/textures/{id}", requirements={"id" = "\d+"}, name="core_wood_texture_show")
	 * @Template("Knowledge/Wood:texture-show-xhr.html.twig")
	 */
	public function textureShow(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$textureRepository = $om->getRepository(Wood\Texture::CLASS_NAME);

		$texture = $textureRepository->findOneById($id);
		if (is_null($texture)) {
			throw $this->createNotFoundException('Unable to find Texture entity (id='.$id.').');
		}

		return array(
			'texture' => $texture,
		);
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_wood_widget")
	 * @Template("Knowledge/Wood:widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$wood = $this->retrievePublication($id, Wood::CLASS_NAME);
		$this->assertShowable($wood, true);

		return array(
			'wood' => $wood,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_wood_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_wood_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_wood_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_wood_list_page")
	 * @Template("Knowledge/Wood:list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_wood_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'name':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
						$filters[] = $elasticaQueryUtils->createShouldMatchPhraseQuery('name', $facet->value);

						break;

					case 'origin':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'origin' ));
						$filters[] = $filter;

						break;

					case 'utilization':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'utilization' ));
						$filters[] = $filter;

						break;

					case 'rejected':

						$filter = new \Elastica\Query\BoolQuery();
						$filter->addShould(new \Elastica\Query\Range('nameRejected', array( 'gte' => 1 )));
						$filter->addShould(new \Elastica\Query\Range('grainRejected', array( 'gte' => 1 )));
						$filters[] = $filter;

						$noGlobalFilters = true;

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

					case 'sort-density':
						$sort = array( 'density' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-alphabetical':
						$sort = array( 'titleWorkaround' => array( 'order' => $searchUtils->getSorterOrder($facet, 'asc') ) );
						break;

					case 'sort-completion':
						$sort = array( 'completion100' => array( 'order' =>  $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'name^100', 'scientificname', 'englishname' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getUnlistedKnowledgeWoodCount() > 0) {
					$sort = array('changedAt' => array('order' => 'desc'));
				} else {
					$sort = array('completion100' => array('order' => 'desc'));
				}

			},
			function(&$filters) {

				$filters[] = new \Elastica\Query\Range('nameRejected', array( 'lt' => 1 ));
				$filters[] = new \Elastica\Query\Range('grainRejected', array( 'lt' => 1 ));

			},
			'knowledge_wood',
			\App\Entity\Knowledge\Wood::CLASS_NAME,
			'core_wood_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'woods' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/Wood:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_wood_show")
	 * @Template("Knowledge/Wood:show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$woodRepository = $om->getRepository(Wood::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$wood = $woodRepository->findOneById($id);
		if (is_null($wood)) {
			if ($response = $witnessManager->checkResponse(Wood::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Wood entity.');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($wood), PublicationListener::PUBLICATION_SHOWN);

		$searchUtils = $this->get(SearchUtils::class);
		$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
		$searchableCreationCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('woods.label', $wood->getName()) ), 'wonder_creation');
		$searchableProviderCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('woodsWorkaround', $wood->getName()) ), 'knowledge_provider');

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		return array(
			'wood'                    => $wood,
			'permissionContext'       => $this->getPermissionContext($wood),
			'searchableCreationCount' => $searchableCreationCount,
			'searchableProviderCount' => $searchableProviderCount,
			'likeContext'             => $likableUtils->getLikeContext($wood, $this->getUser()),
			'watchContext'            => $watchableUtils->getWatchContext($wood, $this->getUser()),
			'commentContext'          => $commentableUtils->getCommentContext($wood),
			'collectionContext'       => $collectionnableUtils->getCollectionContext($wood),
		);
	}

}
