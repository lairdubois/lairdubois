<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Knowledge\Value\SoftwareIdentity;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\ReviewableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Form\Type\Knowledge\NewSoftwareType;
use Ladb\CoreBundle\Form\Model\NewSoftware;
use Ladb\CoreBundle\Entity\Knowledge\Software;
use Ladb\CoreBundle\Entity\Knowledge\Value\Text;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\ElasticaQueryUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Ladb\CoreBundle\Manager\Knowledge\SoftwareManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\KnowledgeEvent;
use Ladb\CoreBundle\Event\KnowledgeListener;

/**
 * @Route("/logiciels")
 */
class SoftwareController extends AbstractController {

	/**
	 * @Route("/new", name="core_software_new")
	 * @Template("LadbCoreBundle:Knowledge/Software:new.html.twig")
	 */
	public function newAction() {
		$knowledgeUtils = $this->get(KnowledgeUtils::NAME);

		$newSoftware = new NewSoftware();
		$form = $this->createForm(NewSoftwareType::class, $newSoftware);

		return array(
			'form'           => $form->createView(),
			'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_software_create")
	 * @Template("LadbCoreBundle:Knowledge/Software:new.html.twig")
	 */
	public function createAction(Request $request) {

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
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($software, array( 'field' => Software::FIELD_IDENTITY, 'value' => $identityValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($software, array( 'field' => Software::FIELD_ICON, 'value' => $iconValue )));

			$identityValue->setParentEntity($software);
			$identityValue->setParentEntityField(Software::FIELD_IDENTITY);
			$identityValue->setUser($user);

			$iconValue->setParentEntity($software);
			$iconValue->setParentEntityField(Software::FIELD_ICON);
			$iconValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new software

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($identityValue, false);
			$activityUtils->createContributeActivity($iconValue, false);

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($software));

			$om->flush();

			// Dispatch publication event
			$dispatcher->dispatch(PublicationListener::PUBLICATION_PUBLISHED, new PublicationEvent($software));

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
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$softwareRepository = $om->getRepository(Software::CLASS_NAME);

		$software = $softwareRepository->findOneById($id);
		if (is_null($software)) {
			throw $this->createNotFoundException('Unable to find Software entity (id='.$id.').');
		}

		// Delete
		$softwareMananger = $this->get(SoftwareManager::NAME);
		$softwareMananger->delete($software);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.software.form.alert.delete_success', array( '%title%' => $software->getTitle() )));

		return $this->redirect($this->generateUrl('core_software_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_software_widget")
	 * @Template("LadbCoreBundle:Knowledge/Software:widget-xhr.html.twig")
	 */
	public function widgetAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$softwareRepository = $om->getRepository(Software::CLASS_NAME);

		$id = intval($id);

		$software = $softwareRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($software)) {
			throw $this->createNotFoundException('Unable to find Software entity (id='.$id.').');
		}

		return array(
			'software' => $software,
		);
	}

	/**
	 * @Route("/", name="core_software_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_software_list_page")
	 * @Template("LadbCoreBundle:Knowledge/Software:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

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

						$elasticaQueryUtils = $this->get(ElasticaQueryUtils::NAME);
						$filters[] = $elasticaQueryUtils->createShouldMatchQuery('languages', $facet->value);

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

				$filters[] = new \Elastica\Query\Range('identityRejected', array( 'lt' => 1 ));
				$filters[] = new \Elastica\Query\Range('iconRejected', array( 'lt' => 1 ));

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			null,
			'fos_elastica.index.ladb.knowledge_software',
			\Ladb\CoreBundle\Entity\Knowledge\Software::CLASS_NAME,
			'core_software_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()));

		$parameters = array_merge($searchParameters, array(
			'softwares' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Knowledge/Software:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_software_show")
	 * @Template("LadbCoreBundle:Knowledge/Software:show.html.twig")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$softwareRepository = $om->getRepository(Software::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$software = $softwareRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($software)) {
			if ($response = $witnessManager->checkResponse(Software::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Software entity.');
		}

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($software));

		$hostSoftware = $software->getIsAddOn() && !is_null($software->getHostSoftwareName()) ? $softwareRepository->findOneByName($software->getHostSoftwareName()) : null;

		$searchUtils = $this->get(SearchUtils::NAME);
		$searchableAddonCount = $software->getIsAddOn() ? 0 : $searchUtils->searchEntitiesCount(array(
			new \Elastica\Query\Term(['isAddOn' => ['value' => true, 'boost' => 1.0]]),
			new \Elastica\Query\Term(['hostSoftwareName' => ['value' => strtolower($software->getName()), 'boost' => 1.0]])
		), 'fos_elastica.index.ladb.knowledge_software');
		$searchablePlanCount = is_null($software->getSupportedFiles()) ? 0 : $searchUtils->searchEntitiesCount(array(
			new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC )),
			new \Elastica\Query\Match('resources.fileExtension', $software->getSupportedFiles())
		), 'fos_elastica.index.ladb.wonder_plan');

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'software'             => $software,
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
