<?php

namespace App\Controller\Knowledge;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Knowledge\Value\SoftwareIdentity;
use App\Model\HiddableInterface;
use App\Utils\CollectionnableUtils;
use App\Utils\ReviewableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\Knowledge\NewSoftwareType;
use App\Form\Model\NewSoftware;
use App\Entity\Knowledge\Software;
use App\Entity\Knowledge\Value\Text;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\ElasticaQueryUtils;
use App\Utils\ActivityUtils;
use App\Utils\KnowledgeUtils;
use App\Manager\Knowledge\SoftwareManager;
use App\Manager\Core\WitnessManager;
use App\Event\PublicationsEvent;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;

/**
 * @Route("/logiciels")
 */
class SoftwareController extends AbstractController {

	use PublicationControllerTrait;

	/**
	 * @Route("/new", name="core_software_new")
	 * @Template("Knowledge/Software:new.html.twig")
	 */
	public function new() {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_software_new)');
		}

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$newSoftware = new NewSoftware();
		$form = $this->createForm(NewSoftwareType::class, $newSoftware);

		return array(
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_software_create")
	 * @Template("Knowledge/Software:new.html.twig")
	 */
	public function create(Request $request) {

		// Exclude if user is not email confirmed
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('Not allowed - User email not confirmed (core_software_create)');
		}

		$this->createLock('core_software_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newSoftware = new NewSoftware();
		$form = $this->createForm(NewSoftwareType::class, $newSoftware);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$identityValue = $newSoftware->getIdentityValue();
			$iconValue = $newSoftware->getIconValue();
			$user = $this->getUser();

			// Sanitize Identity values
			if ($identityValue instanceof SoftwareIdentity) {
				$identityValue->setData(trim(ucfirst($identityValue->getData())));
			}

			$software = new Software();
			$software->setTitle($identityValue->getData());
			$software->incrementContributorCount();

			$om->persist($software);
			$om->flush();	// Need to save software to be sure ID is generated

			$software->addIdentityValue($identityValue);
			$software->addIconValue($iconValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(new KnowledgeEvent($software, array( 'field' => Software::FIELD_IDENTITY, 'value' => $identityValue )), KnowledgeListener::FIELD_VALUE_ADDED);
			$dispatcher->dispatch(new KnowledgeEvent($software, array( 'field' => Software::FIELD_ICON, 'value' => $iconValue )), KnowledgeListener::FIELD_VALUE_ADDED);

			$identityValue->setParentEntity($software);
			$identityValue->setParentEntityField(Software::FIELD_IDENTITY);
			$identityValue->setUser($user);

			$iconValue->setParentEntity($software);
			$iconValue->setParentEntityField(Software::FIELD_ICON);
			$iconValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new software

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createContributeActivity($identityValue, false);
			$activityUtils->createContributeActivity($iconValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($software), PublicationListener::PUBLICATION_CREATED);

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(new PublicationEvent($software), PublicationListener::PUBLICATION_PUBLISHED);

			return $this->redirect($this->generateUrl('core_software_show', array('id' => $software->getSluggedId())));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'newSoftware' => $newSoftware,
			'form'        => $form->createView(),
			'hideWarning' => true,
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_software_delete")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_software_delete)")
	 */
	public function delete($id) {

		$software = $this->retrievePublication($id, Software::CLASS_NAME);
		$this->assertDeletable($software);

		// Delete
		$softwareMananger = $this->get(SoftwareManager::class);
		$softwareMananger->delete($software);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.software.form.alert.delete_success', array( '%title%' => $software->getTitle() )));

		return $this->redirect($this->generateUrl('core_software_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_software_widget")
	 * @Template("Knowledge/Software:widget-xhr.html.twig")
	 */
	public function widget(Request $request, $id) {

		$software = $this->retrievePublication($id, Software::CLASS_NAME);
		$this->assertShowable($software, true);

		return array(
			'software' => $software,
		);
	}

	/**
	 * @Route("/", name="core_software_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_software_list_page")
	 * @Template("Knowledge/Software:list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_software_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'authors':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'authors' ));
						$filters[] = $filter;

						break;

					case 'publisher':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'publisher' ));
						$filters[] = $filter;

						break;

					case 'addon':

						$filters[] = new \Elastica\Query\Term(['isAddOn' => ['value' => true, 'boost' => 1.0]]);
						$filters[] = new \Elastica\Query\Term(['hostSoftwareName' => ['value' => strtolower($facet->value), 'boost' => 1.0]]);

						break;

					case 'os':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'operatingSystems' ));
						$filters[] = $filter;

						break;

					case 'pricings':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'pricings' ));
						$filters[] = $filter;

						break;

					case 'features':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'features' ));
						$filters[] = $filter;

						break;

					case 'languages':

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
						$filters[] = $elasticaQueryUtils->createShouldMatchPhraseQuery('languages', $facet->value);

						break;

					case 'open-source':

						$filters[] = new \Elastica\Query\Term(['openSource' => ['value' => true, 'boost' => 1.0]]);

						break;

					case 'supported-files':

						$filters[] = new \Elastica\Query\Match('supportedFiles', $facet->value);

						break;

					case 'with-review':

						$filter = new \Elastica\Query\Range('reviewCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'rejected':

						$filter = new \Elastica\Query\BoolQuery();
						$filter->addShould(new \Elastica\Query\Range('identityRejected', array( 'gte' => 1 )));
						$filter->addShould(new \Elastica\Query\Range('iconRejected', array( 'gte' => 1 )));
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

					case 'sort-popular-rating':
						$sort = array( 'averageRating' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
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
							$filter->setFields(array( 'name^100', 'hostSoftwareName^50', 'publisher', 'description', 'features' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$filters[] = new \Elastica\Query\Range('identityRejected', array( 'lt' => 1 ));
				$filters[] = new \Elastica\Query\Range('iconRejected', array( 'lt' => 1 ));

			},
			'knowledge_software',
			\App\Entity\Knowledge\Software::CLASS_NAME,
			'core_software_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'softwares' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Knowledge/Software:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_software_show")
	 * @Template("Knowledge/Software:show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$softwareRepository = $om->getRepository(Software::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$software = $softwareRepository->findOneById($id);
		if (is_null($software)) {
			if ($response = $witnessManager->checkResponse(Software::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Software entity.');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($software), PublicationListener::PUBLICATION_SHOWN);

		$hostSoftware = $software->getIsAddOn() && !is_null($software->getHostSoftwareName()) ? $softwareRepository->findOneByName($software->getHostSoftwareName()) : null;

		$searchUtils = $this->get(SearchUtils::class);
		$searchableAddonCount = $software->getIsAddOn() ? 0 : $searchUtils->searchEntitiesCount(array(
			new \Elastica\Query\Term(['isAddOn' => ['value' => true, 'boost' => 1.0]]),
			new \Elastica\Query\Term(['hostSoftwareName' => ['value' => strtolower($software->getName()), 'boost' => 1.0]])
		), 'knowledge_software');
		$searchablePlanCount = is_null($software->getSupportedFiles()) ? 0 : $searchUtils->searchEntitiesCount(array(
			new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC )),
			new \Elastica\Query\Match('resources.fileExtension', $software->getSupportedFiles())
		), 'wonder_plan');

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);

		return array(
			'software'             => $software,
			'permissionContext'    => $this->getPermissionContext($software),
			'hostSoftware'         => $hostSoftware,
			'searchableAddonCount' => $searchableAddonCount,
			'searchablePlanCount'  => $searchablePlanCount,
			'likeContext'          => $likableUtils->getLikeContext($software, $this->getUser()),
			'watchContext'         => $watchableUtils->getWatchContext($software, $this->getUser()),
			'commentContext'       => $commentableUtils->getCommentContext($software),
			'collectionContext'    => $collectionnableUtils->getCollectionContext($software),
			'reviewContext'        => $reviewableUtils->getReviewContext($software),
		);
	}

}
