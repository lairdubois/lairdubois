<?php

namespace App\Controller\Faq;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Utils\CollectionnableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Faq\Question;
use App\Form\Type\Faq\QuestionType;
use App\Utils\TagUtils;
use App\Utils\CommentableUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\SearchUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\ExplorableUtils;
use App\Utils\BlockBodiedUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Manager\Faq\QuestionManager;
use App\Manager\Core\WitnessManager;
use App\Model\HiddableInterface;

/**
 * @Route("/faq")
 */
class QuestionController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.QuestionManager::class,
        ));
    }

	/**
	 * @Route("/new", name="core_faq_question_new")
	 * @Template("Faq/Question/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_FAQ')", statusCode=404, message="Not allowed (core_faq_question_new)")
	 */
	public function new(Request $request) {

		$question = new Question();
		$question->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(QuestionType::class, $question);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_faq_question_create")
	 * @Template("Faq/Question/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_FAQ')", statusCode=404, message="Not allowed (core_faq_question_create)")
	 */
	public function create(Request $request) {

		$this->createLock('core_faq_question_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$question = new Question();
		$form = $this->createForm(QuestionType::class, $question);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
			$blockBodiedUtils->preprocessBlocks($question);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($question);

			if ($question->getBodyBlockPictureCount() > 0) {
				$question->setMainPicture($blockBodiedUtils->getFirstPicture($question));
			}

			$question->setUser($this->getUser());

			$om->persist($question);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($question), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_faq_question_show', array( 'id' => $question->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_faq_question_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_faq_question_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertLockUnlockable($question, $lock);

		// Lock or Unlock
		$postManager = $this->get(QuestionManager::class);
		if ($lock) {
			$postManager->lock($question);
		} else {
			$postManager->unlock($question);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_faq_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_faq_question_publish")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_FAQ')", statusCode=404, message="Not allowed (core_faq_question_publish)")
	 */
	public function publish($id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertPublishable($question);

		// Publish
		$questionManager = $this->get(QuestionManager::class);
		$questionManager->publish($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.publish_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_faq_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_faq_question_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertUnpublishable($question);

		// Unpublish
		$questionManager = $this->get(QuestionManager::class);
		$questionManager->unpublish($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.unpublish_success', array( '%title%' => $question->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_faq_question_edit")
	 * @Template("Faq/Question/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_FAQ')", statusCode=404, message="Not allowed (core_faq_question_edit)")
	 */
	public function edit($id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertEditabable($question);

		$form = $this->createForm(QuestionType::class, $question);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_faq_question_update")
	 * @Template("Faq/Question/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_FAQ')", statusCode=404, message="Not allowed (core_faq_question_update)")
	 */
	public function update(Request $request, $id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertEditabable($question);

		$originalBodyBlocks = $question->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $question->getTags()->toArray();	// Need to be an array to copy values

		$question->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(QuestionType::class, $question);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockBodiedUtils = $this->get(BlockBodiedUtils::class);
			$blockBodiedUtils->preprocessBlocks($question, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($question);

			if ($question->getBodyBlockPictureCount() > 0) {
				$question->setMainPicture($blockBodiedUtils->getFirstPicture($question));
			}

			if ($question->getUser() == $this->getUser()) {
				$question->setUpdatedAt(new \DateTime());
			}

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($question, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.update_success', array( '%title%' => $question->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(QuestionType::class, $question);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'question'     => $question,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($question),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_faq_question_delete")
	 */
	public function delete($id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertDeletable($question);

		// Delete
		$questionManager = $this->get(QuestionManager::class);
		$questionManager->delete($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('faq.question.form.alert.delete_success', array( '%title%' => $question->getTitle() )));

		return $this->redirect($this->generateUrl('core_faq_question_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_faq_question_widget")
	 * @Template("Faq/Question/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$question = $this->retrievePublication($id, Question::CLASS_NAME);
		$this->assertShowable($question, true);

		return array(
			'question' => $question,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_faq_question_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_faq_question_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_faq_question_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_faq_question_list_page")
	 * @Template("Faq/Question/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_faq_question_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'admin-all':
						if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

							$filters[] = new \Elastica\Query\MatchAll();

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

							$noGlobalFilters = true;
						}
						break;

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

					case 'sort-important':
						$sort = array( 'weight' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body', 'tags.label' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $this->getUser()->getMeta()->getUnlistedFaqQuestionCount() > 0) {
					$sort = array('changedAt' => array('order' => 'desc'));
				} else {
					$sort = array( 'weight' => array( 'order' => 'desc' ) );
				}

			},
			function(&$filters) {

				$this->pushGlobalVisibilityFilter($filters, true, false);

			},
			'faq_question',
			\App\Entity\Faq\Question::CLASS_NAME,
			'core_faq_question_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'questions' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Faq/Question/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", requirements={"slug" = "[a-z-]+"}, name="core_faq_question_show")
	 * @Template("Faq/Question/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		if (intval($id) == 0) {
			$question = $questionRepository->findOneBySlugJoinedOnAll($id);
		} else {
			$id = intval($id);
			$question = $questionRepository->findOneById($id);
		}
		if (is_null($question)) {
			if ($response = $witnessManager->checkResponse(Question::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}
		$this->assertShowable($question);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$similarQuestions = $explorableUtils->getSimilarExplorables($question, 'faq_question', Question::CLASS_NAME, null, 10);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($question), PublicationListener::PUBLICATION_SHOWN);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'question'          => $question,
			'permissionContext' => $this->getPermissionContext($question),
			'similarQuestions'  => $similarQuestions,
			'likeContext'       => $likableUtils->getLikeContext($question, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($question, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($question),
			'collectionContext' => $collectionnableUtils->getCollectionContext($question),
			'followerContext'   => $followerUtils->getFollowerContext($question->getUser(), $this->getUser()),
		);
	}

}
