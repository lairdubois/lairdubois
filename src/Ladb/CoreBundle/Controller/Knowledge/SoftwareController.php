<?php

namespace Ladb\CoreBundle\Controller\Knowledge;

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
class SoftwareController extends Controller {

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
		$om = $this->getDoctrine()->getManager();
		$dispatcher = $this->get('event_dispatcher');

		$newSoftware = new NewSoftware();
		$form = $this->createForm(NewSoftwareType::class, $newSoftware);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$applicationValue = $newSoftware->getApplicationValue();
			$iconValue = $newSoftware->getIconValue();
			$user = $this->getUser();

			// Sanitize Application values
			if ($applicationValue instanceof Text) {
				$applicationValue->setData(trim(ucfirst($applicationValue->getData())));
			}

			$software = new Software();
			$software->setTitle($applicationValue->getData());
			$software->incrementContributorCount();

			$om->persist($software);
			$om->flush();	// Need to save software to be sure ID is generated

			$software->addApplicationValue($applicationValue);
			$software->addIconValue($iconValue);

			// Dispatch knowledge events
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($software, array( 'field' => Software::FIELD_APPLICATION, 'value' => $applicationValue )));
			$dispatcher->dispatch(KnowledgeListener::FIELD_VALUE_ADDED, new KnowledgeEvent($software, array( 'field' => Software::FIELD_ICON, 'value' => $iconValue )));

			$applicationValue->setParentEntity($software);
			$applicationValue->setParentEntityField(Software::FIELD_APPLICATION);
			$applicationValue->setUser($user);

			$iconValue->setParentEntity($software);
			$iconValue->setParentEntityField(Software::FIELD_ICON);
			$iconValue->setUser($user);

			$user->getMeta()->incrementProposalCount(2);	// Name and Grain of this new software

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createContributeActivity($applicationValue, false);
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
			'newSoftware'     => $newSoftware,
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
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) {
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

						$filters[] = new \Elastica\Query\Range('isAddOn', array( 'gte' => 1 ));
						$filters[] = new \Elastica\Query\Match('hostSoftware', $facet->value);

						break;

					case 'os':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'operatingSystems' ));
						$filters[] = $filter;

						break;

					case 'licenses':

						$filter = new \Elastica\Query\QueryString('"'.$facet->value.'"');
						$filter->setFields(array( 'licenses' ));
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

						$filters[] = new \Elastica\Query\Range('openSource', array( 'gte' => 1 ));

						break;

					case 'with-review':

						$filter = new \Elastica\Query\Range('reviewCount', array( 'gt' => 0 ));
						$filters[] = $filter;

						break;

					case 'rejected':

						$filter = new \Elastica\Query\BoolQuery();
						$filter->addShould(new \Elastica\Query\Range('applicationRejected', array( 'gte' => 1 )));
						$filter->addShould(new \Elastica\Query\Range('iconRejected', array( 'gte' => 1 )));
						$filters[] = $filter;

						break;

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

					case 'sort-popular-rating':
						$sort = array( 'averageRating' => array( 'order' => 'desc' ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'name^100', 'hostSoftware^50', 'publisher' ));
							$filters[] = $filter;

						}

				}
			},
			function(&$filters, &$sort) {

				$filters[] = new \Elastica\Query\Range('applicationRejected', array( 'lt' => 1 ));
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

		$searchUtils = $this->get(SearchUtils::NAME);
		$searchableAddonCount = $software->getIsAddOn() ? 0 : $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Range('isAddOn', array( 'gte' => 1 )), new \Elastica\Query\Match('hostSoftware', $software->getName()) ), 'fos_elastica.index.ladb.knowledge_software');

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);

		return array(
			'software'             => $software,
			'searchableAddonCount' => $searchableAddonCount,
			'likeContext'          => $likableUtils->getLikeContext($software, $this->getUser()),
			'watchContext'         => $watchableUtils->getWatchContext($software, $this->getUser()),
			'commentContext'       => $commentableUtils->getCommentContext($software),
			'collectionContext'    => $collectionnableUtils->getCollectionContext($software),
			'reviewContext'        => $reviewableUtils->getReviewContext($software),
		);
	}

}
