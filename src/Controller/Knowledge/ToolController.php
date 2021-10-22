<?php

namespace App\Controller\Knowledge;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Utils\CollectionnableUtils;
use App\Utils\ReviewableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\Knowledge\NewToolType;
use App\Form\Model\NewTool;
use App\Entity\Knowledge\Tool;
use App\Entity\Knowledge\Value\Text;
use App\Utils\CommentableUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\ElasticaQueryUtils;
use App\Utils\ActivityUtils;
use App\Utils\KnowledgeUtils;
use App\Manager\Knowledge\ToolManager;
use App\Manager\Core\WitnessManager;
use App\Event\PublicationsEvent;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\KnowledgeEvent;
use App\Event\KnowledgeListener;

/**
 * @Route("/outils")
 */
class ToolController extends AbstractController {

    use PublicationControllerTrait;

    /**
     * @Route("/new", name="core_tool_new")
     * @Template("Knowledge/Tool/new.html.twig")
     */
    public function newAction() {

        // Exclude if user is not email confirmed
        if (!$this->getUser()->getEmailConfirmed()) {
            throw $this->createNotFoundException('Not allowed - User email not confirmed (core_tool_new)');
        }

        $knowledgeUtils = $this->get(KnowledgeUtils::class);

        $newTool = new NewTool();
        $form = $this->createForm(NewToolType::class, $newTool);

        return array(
            'form'           => $form->createView(),
            'sourcesHistory' => $knowledgeUtils->getValueSourcesHistory(),
        );
    }

    /**
     * @Route("/create", methods={"POST"}, name="core_tool_create")
     * @Template("Knowledge/Tool/new.html.twig")
     */
    public function createAction(Request $request) {

        // Exclude if user is not email confirmed
        if (!$this->getUser()->getEmailConfirmed()) {
            throw $this->createNotFoundException('Not allowed - User email not confirmed (core_tool_create)');
        }

        $this->createLock('core_tool_create', false, self::LOCK_TTL_CREATE_ACTION, false);

        $om = $this->getDoctrine()->getManager();
        $dispatcher = $this->get('event_dispatcher');

        $newTool = new NewTool();
        $form = $this->createForm(NewToolType::class, $newTool);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $nameValue = $newTool->getNameValue();
            $photoValue = $newTool->getPhotoValue();
            $productNameValue = $newTool->getProductNameValue();
            $brandValue = $newTool->getBrandValue();
            $user = $this->getUser();

            $productNameDefined = !empty($productNameValue->getData());
            $brandDefined = !empty($brandValue->getData());

            // Sanitize Identity values
            if ($nameValue instanceof Text) {
                $nameValue->setData(trim(ucfirst($nameValue->getData())));
            }

            $tool = new Tool();
            $tool->setTitle($nameValue->getData());
            $tool->incrementContributorCount();

            $om->persist($tool);
            $om->flush();	// Need to save tool to be sure ID is generated

            $tool->addNameValue($nameValue);
            $tool->addPhotoValue($photoValue);
            if ($productNameDefined) $tool->addProductNameValue($productNameValue);
            if ($brandDefined) $tool->addBrandValue($brandValue);

            // Dispatch knowledge events
            $dispatcher->dispatch(new KnowledgeEvent($tool, array( 'field' => Tool::FIELD_NAME, 'value' => $nameValue )), KnowledgeListener::FIELD_VALUE_ADDED);
            $dispatcher->dispatch(new KnowledgeEvent($tool, array( 'field' => Tool::FIELD_PHOTO, 'value' => $photoValue )), KnowledgeListener::FIELD_VALUE_ADDED);
            if ($productNameDefined) $dispatcher->dispatch(new KnowledgeEvent($tool, array( 'field' => Tool::FIELD_PRODUCT_NAME, 'value' => $productNameValue )), KnowledgeListener::FIELD_VALUE_ADDED);
            if ($brandDefined) $dispatcher->dispatch(new KnowledgeEvent($tool, array( 'field' => Tool::FIELD_BRAND, 'value' => $brandValue )), KnowledgeListener::FIELD_VALUE_ADDED);

            $nameValue->setParentEntity($tool);
            $nameValue->setParentEntityField(Tool::FIELD_NAME);
            $nameValue->setUser($user);

            $photoValue->setParentEntity($tool);
            $photoValue->setParentEntityField(Tool::FIELD_PHOTO);
            $photoValue->setUser($user);

            if ($productNameDefined) {
                $productNameValue->setParentEntity($tool);
                $productNameValue->setParentEntityField(Tool::FIELD_PRODUCT_NAME);
                $productNameValue->setUser($user);
            }

            if ($brandDefined) {
                $brandValue->setParentEntity($tool);
                $brandValue->setParentEntityField(Tool::FIELD_BRAND);
                $brandValue->setUser($user);
            }

            $user->getMeta()->incrementProposalCount(2);	// Name and Photo of this new tool
            if ($productNameDefined) $user->getMeta()->incrementProposalCount(1);
            if ($brandDefined) $user->getMeta()->incrementProposalCount(1);

            // Create activity
            $activityUtils = $this->get(ActivityUtils::class);
            $activityUtils->createContributeActivity($nameValue, false);
            $activityUtils->createContributeActivity($photoValue, false);
            if ($productNameDefined) $activityUtils->createContributeActivity($productNameValue, false);
            if ($brandDefined) $activityUtils->createContributeActivity($brandValue, false);

            // Dispatch publication event
            $dispatcher->dispatch(new PublicationEvent($tool), PublicationListener::PUBLICATION_CREATED);

            $om->flush();

            // Dispatch publication event
            $dispatcher->dispatch(new PublicationEvent($tool), PublicationListener::PUBLICATION_PUBLISHED);

            return $this->redirect($this->generateUrl('core_tool_show', array('id' => $tool->getSluggedId())));
        }

        // Flashbag
        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

        return array(
            'newTool'     => $newTool,
            'form'        => $form->createView(),
            'hideWarning' => true,
        );
    }

    /**
     * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_tool_delete")
     * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_tool_delete)")
     */
    public function deleteAction($id) {

        $tool = $this->retrievePublication($id, Tool::CLASS_NAME);
        $this->assertDeletable($tool);

        // Delete
        $toolMananger = $this->get(ToolManager::class);
        $toolMananger->delete($tool);

        // Flashbag
        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('knowledge.tool.form.alert.delete_success', array( '%title%' => $tool->getTitle() )));

        return $this->redirect($this->generateUrl('core_tool_list'));
    }

    /**
     * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_tool_widget")
     * @Template("Knowledge/Tool/widget-xhr.html.twig")
     */
    public function widgetAction($id) {

        $tool = $this->retrievePublication($id, Tool::CLASS_NAME);
        $this->assertShowable($tool, true);

        return array(
            'tool' => $tool,
        );
    }

    /**
     * @Route("/", name="core_tool_list")
     * @Route("/{page}", requirements={"page" = "\d+"}, name="core_tool_list_page")
     * @Template("Knowledge/Tool/list.html.twig")
     */
    public function listAction(Request $request, $page = 0) {
        $searchUtils = $this->get(SearchUtils::class);

        // Elasticsearch paginiation limit
        if ($page > 624) {
            throw $this->createNotFoundException('Page limit reached (core_tool_list_page)');
        }

        $searchParameters = $searchUtils->searchPaginedEntities(
            $request,
            $page,
            function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
                switch ($facet->name) {

                    // Filters /////

                    case 'name':

                        if (strpos($facet->value, ',')) {
                            $filter = new \Elastica\Query\Match('name', $facet->value);
                        } else {
                            $filter = new \Elastica\Query\Match('nameKeyword', $facet->value);
                        }
                        $filters[] = $filter;

                        break;

                    case 'brand':

                        if (strpos($facet->value, '*')) {
                            $filter = new \Elastica\Query\QueryString($facet->value);
                            $filter->setFields(array( 'brand' ));
                        } else {
                            $filter = new \Elastica\Query\Match('brand', $facet->value);
                        }
                        $filters[] = $filter;

                        break;

                    case 'family':

                        $filter = new \Elastica\Query\Match('family', $facet->value);
                        $filters[] = $filter;

                        break;

                    case 'with-manual':

                        $filter = new \Elastica\Query\Range('manualCount', array( 'gt' => 0 ));
                        $filters[] = $filter;

                        break;

                    case 'with-review':

                        $filter = new \Elastica\Query\Range('reviewCount', array( 'gt' => 0 ));
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
                        $filter->addShould(new \Elastica\Query\Range('photoRejected', array( 'gte' => 1 )));
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

                    case 'sort-alphabetical':
                        $sort = array( 'nameKeyword' => array( 'order' => $searchUtils->getSorterOrder($facet, 'asc') ) );
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
                            $filter->setFields(array( 'name^100', 'englishName^50', 'productName^50', 'brand', 'description' ));
                            $filters[] = $filter;

                            $couldUseDefaultSort = false;

                        }

                }
            },
            function(&$filters, &$sort) {

                $sort = array( 'changedAt' => array( 'order' => 'desc' ) );

            },
            function(&$filters) {
                // Todo handle boolean search
//                $filters[] = new \Elastica\Query\Range('nameRejected', array( 'eq' == 'false' ));
//                $filters[] = new \Elastica\Query\Range('photoRejected', array( 'eq' == 'false' ));

            },
            'knowledge_tool',
            \App\Entity\Knowledge\Tool::CLASS_NAME,
            'core_tool_list_page'
        );

        // Dispatch publication event
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

        $parameters = array_merge($searchParameters, array(
            'tools' => $searchParameters['entities'],
        ));

        if ($request->isXmlHttpRequest()) {
            return $this->render('Knowledge/Tool/list-xhr.html.twig', $parameters);
        }

        return $parameters;
    }

    /**
     * @Route("/{id}.html", name="core_tool_show")
     * @Template("Knowledge/Tool/show.html.twig")
     */
    public function showAction(Request $request, $id) {
        $om = $this->getDoctrine()->getManager();
        $toolRepository = $om->getRepository(Tool::CLASS_NAME);
        $witnessManager = $this->get(WitnessManager::class);

        $id = intval($id);

        $tool = $toolRepository->findOneById($id);
        if (is_null($tool)) {
            if ($response = $witnessManager->checkResponse(Tool::TYPE, $id)) {
                return $response;
            }
            throw $this->createNotFoundException('Unable to find Tool entity.');
        }

        // Dispatch publication event
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(new PublicationEvent($tool), PublicationListener::PUBLICATION_SHOWN);

        $searchUtils = $this->get(SearchUtils::class);
        $elasticaQueryUtils = $this->get(ElasticaQueryUtils::class);
        $searchableBrotherCount = $searchUtils->searchEntitiesCount(array( new \Elastica\Query\Match('nameKeyword', $tool->getName()) ), 'fos_elastica.index.ladb.knowledge_tool');
        $searchableCreationCount = $searchUtils->searchEntitiesCount(array( $elasticaQueryUtils->createShouldMatchPhraseQuery('tools.label', $tool->getTitle()) ), 'fos_elastica.index.ladb.wonder_creation');

        $likableUtils = $this->get(LikableUtils::class);
        $watchableUtils = $this->get(WatchableUtils::class);
        $commentableUtils = $this->get(CommentableUtils::class);
        $reviewableUtils = $this->get(ReviewableUtils::class);
        $collectionnableUtils = $this->get(CollectionnableUtils::class);

        return array(
            'tool'                    => $tool,
            'permissionContext'       => $this->getPermissionContext($tool),
            'searchableBrotherCount'  => $searchableBrotherCount,
            'searchableCreationCount' => $searchableCreationCount,
            'likeContext'             => $likableUtils->getLikeContext($tool, $this->getUser()),
            'watchContext'            => $watchableUtils->getWatchContext($tool, $this->getUser()),
            'commentContext'          => $commentableUtils->getCommentContext($tool),
            'collectionContext'       => $collectionnableUtils->getCollectionContext($tool),
            'reviewContext'           => $reviewableUtils->getReviewContext($tool),
        );
    }

}