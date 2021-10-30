<?php

namespace App\Controller\Qa;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Qa\Answer;
use App\Entity\Qa\Question;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Form\Type\Qa\AnswerType;
use App\Manager\Qa\AnswerManager;
use App\Manager\Qa\QuestionManager;
use App\Utils\ActivityUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\CommentableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\MentionUtils;
use App\Utils\SearchUtils;
use App\Utils\VotableUtils;
use App\Utils\WatchableUtils;
use App\Utils\WebpushNotificationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * @Route("/questions")
 */
class AnswerController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.AnswerManager::class,
            '?'.QuestionManager::class,
            '?'.BlockBodiedUtils::class,
            '?'.CommentableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.MentionUtils::class,
            '?'.SearchUtils::class,
            '?'.VotableUtils::class,
            '?'.WatchableUtils::class,
            '?'.WebpushNotificationUtils::class,
        ));
    }

	/**
	 * @Route("/{id}/reponses/new", requirements={"id" = "\d+"}, name="core_qa_answer_new")
	 * @Template("Qa/Answer/new-xhr.html.twig")
	 */
	public function new(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_qa_answer_new)');
		}

		$question = $this->retrievePublication($id, Question::CLASS_NAME);

		$answer = new Answer();
		$answer->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(AnswerType::class, $answer);

		return array(
			'question' => $question,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/reponses/create", requirements={"id" = "\d+"}, methods={"POST"}, name="core_qa_answer_create")
	 * @Template("Qa/Answer/new-xhr.html.twig")
	 */
	public function create(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_qa_answer_create)');
		}

		$this->createLock('core_qa_answer_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$question = $this->retrievePublication($id, Question::CLASS_NAME);

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);
		if ($answerRepository->existsByQuestionAndUser($question, $this->getUser())) {
			throw $this->createNotFoundException('Only one answer allowed (id='.$id.').');
		}

		$answer = new Answer();
		$answer->setQuestion($question);		// Used by validator
		$answer->setUser($this->getUser());		// Used by validator
		$form = $this->createForm(AnswerType::class, $answer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($answer);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($answer);

			$answer->setUser($this->getUser());
			$answer->setParentEntity($question);
			$answer->setParentEntityField('bestAnswer');
			$answer->setUpdatedAt(new \DateTime());

			$question->addAnswer($answer);
			$question->setChangedAt(new \DateTime());

			$question->incrementAnswerCount();
			$this->getUser()->getMeta()->incrementAnswerCount();

			$om->persist($answer);
			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->processMentions($answer);

			// Compute answer counters
			$questionManager = $this->get(QuestionManager::class);
			$questionManager->computeAnswerCounters($question);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createAnswerActivity($answer, false);

			// Dispatch publication event (on Question)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($question), PublicationListener::PUBLICATION_CHANGED);

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::class);
			$watchableUtils->autoCreateWatch($question, $this->getUser());

			if ($answer->getUser() !== $question->getUser()) {
				// Publish a webpush notification in queue
				$webpushNotificationUtils = $this->get(WebpushNotificationUtils::class);
				$webpushNotificationUtils->enqueueNewAnswerNotification($answer, $question);
			}

			$commentableUtils = $this->get(CommentableUtils::class);
			$votableUtils = $this->get(VotableUtils::class);

			return $this->render('Qa/Answer/create-xhr.html.twig', array(
				'question'       => $question,
				'answer'         => $answer,
				'commentContext' => $commentableUtils->getCommentContext($answer, $this->getUser(), false),
				'voteContext'    => $votableUtils->getVoteContext($answer, $this->getUser()),
			));
		}

		return array(
			'question' => $question,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/reponses/{id}/edit", requirements={"id" = "\d+"}, name="core_qa_answer_edit")
	 * @Template("Qa/Answer/edit-xhr.html.twig")
	 */
	public function edit(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_qa_answer_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $answer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_qa_answer_edit)');
		}

		$form = $this->createForm(AnswerType::class, $answer);

		return array(
			'answer' => $answer,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/reponses/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_qa_answer_update")
	 * @Template("Qa/Answer/edit-xhr.html.twig")
	 */
	public function update(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_qa_answer_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $answer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_qa_answer_update)');
		}

		$originalBodyBlocks = $answer->getBodyBlocks()->toArray();	// Need to be an array to copy values

		$answer->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(AnswerType::class, $answer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($answer, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($answer);

			if ($answer->getUser() == $this->getUser()) {
				$answer->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Process mentions
			$mentionUtils = $this->get(MentionUtils::class);
			$mentionUtils->processMentions($answer);

			// Dispatch publication event (on Question)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($answer->getQuestion()), PublicationListener::PUBLICATION_UPDATED);

			$commentableUtils = $this->get(CommentableUtils::class);
			$votableUtils = $this->get(VotableUtils::class);

			return $this->render('Qa/Answer/update-xhr.html.twig', array(
				'question'       => $answer->getQuestion(),
				'answer'         => $answer,
				'commentContext' => $commentableUtils->getCommentContext($answer, $this->getUser(), false),
				'voteContext'    => $votableUtils->getVoteContext($answer, $this->getUser()),
			));
		}

		return array(
			'answer' => $answer,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/reponses/{id}/best/create", requirements={"id" = "\d+"}, defaults={"action" = "create"}, name="core_qa_answer_best_create")
	 * @Route("/reponses/{id}/best/delete", requirements={"id" = "\d+"}, defaults={"action" = "delete"}, name="core_qa_answer_best_delete")
	 */
	public function bestToggle(Request $request, $id, $action) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		if ($action == 'create' && $answer->getVoteScore() < 0) {
			throw $this->createNotFoundException('Not allowed on negative voteScore answer.');
		}

		$question = $answer->getQuestion();

		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $question->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_qa_answer_best_create or core_qa_answer_best_delete)');
		}

		if ($action == 'delete') {
			$oldBestAnswer = $question->getBestAnswer();
			if (!is_null($oldBestAnswer) && $oldBestAnswer->getId() != $answer->getId()) {
				throw $this->createNotFoundException('Not the best answer (core_qa_answer_best_delete)');
			}
			$question->setBestAnswer(null);
		} else {
			$question->setBestAnswer($answer);
		}

		$om->flush();

		// Dispatch publication event (on Question)
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($question), PublicationListener::PUBLICATION_UPDATED);

		return $this->redirect($this->generateUrl('core_qa_answer_show', array( 'id' => $answer->getId() )));
	}

	/**
	 * @Route("/reponses/{id}/delete", requirements={"id" = "\d+"}, name="core_qa_answer_delete")
	 */
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $answer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_qa_answer_delete)');
		}

		$question = $answer->getQuestion();

		// Delete
		$answerManager = $this->get(AnswerManager::class);
		$answerManager->delete($answer);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('qa.answer.form.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/reponses/{id}", requirements={"id" = "\d+"}, name="core_qa_answer_show")
	 */
	public function show($id) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}

		$question = $answer->getQuestion();
		if ($question->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && (is_null($this->getUser()) || $question->getUser()->getId() != $this->getUser()->getId())) {
				throw $this->createNotFoundException('Not allowed (core_qa_answer_show)');
			}
		}

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )).'#_answer_'.$answer->getId());
	}

	/**
	 * @Route("/{id}/reponses", requirements={"id" = "\d+"}, name="core_qa_answer_list")
	 * @Route("/{id}/reponses/{sorter}", requirements={"id" = "\d+", "sorter" = "[a-z-]+"}, name="core_qa_answer_list_sorter")
	 * @Template("Qa/Answer/list-xhr.html.twig")
	 */
	public function list(Request $request, $id, $sorter = 'score') {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_qa_answer_list_sorter)');
		}

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(\App\Entity\Qa\Question::CLASS_NAME);
		$answerRepository = $om->getRepository(\App\Entity\Qa\Answer::CLASS_NAME);

		$id = intval($id);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$answers = $answerRepository->findByQuestion($question, $sorter);

		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);

		return array(
			'sorter'          => $sorter,
			'question'        => $question,
			'answers'         => $answers,
			'voteContexts'    => $votableUtils->getVoteContexts($question->getAnswers(), $this->getUser()),
			'commentContexts' => $commentableUtils->getCommentContexts($question->getAnswers(), false),
		);
	}

	/**
	 * @Route("/{id}/reponses/admin/converttocomment", requirements={"id" = "\d+"}, name="core_qa_answer_admin_converttocomment")
	 * @Security("is_granted('ROLE_ADMIN')", statusCode=404, message="Not allowed (core_qa_answer_admin_converttocomment)")
	 */
	public function adminConvertToComment($id) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		$question = $answer->getQuestion();

		// Convert
		$answerManager = $this->get(AnswerManager::class);
		$find = $answerManager->convertToComment($answer, $question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('qa.answer.admin.alert.converttocomment_success', array( '%title%' => $answer->getTitle() )));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

}
