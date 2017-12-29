<?php

namespace Ladb\CoreBundle\Controller\Qa;

use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Qa\QuestionManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Form\Type\Qa\AnswerType;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\VotableUtils;
use Ladb\CoreBundle\Manager\Qa\AnswerManager;

/**
 * @Route("/questions")
 */
class AnswerController extends Controller {

	/**
	 * @Route("/{id}/answer/new", requirements={"id" = "\d+"}, name="core_qa_answer_new")
	 * @Template("LadbCoreBundle:Qa/Answer:new-xhr.html.twig")
	 */
	public function newAction($id) {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$answer = new Answer();
		$answer->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(AnswerType::class, $answer);

		return array(
			'question' => $question,
			'form'     => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/answer/create", requirements={"id" = "\d+"}, name="core_qa_answer_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Qa/Answer:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$user = $this->getUser();
		foreach ($question->getAnswers() as $answer) {
			if ($answer->getUser()->getId() == $user->getId()) {
				throw $this->createNotFoundException('Only one answer allowed (id='.$id.').');
			}
		}

		$answer = new Answer();
		$answer->setQuestion($question);		// Used by validator
		$answer->setUser($this->getUser());		// Used by validator
		$form = $this->createForm(AnswerType::class, $answer);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($answer);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
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

			// Compute answer counters
			$questionManager = $this->get(QuestionManager::NAME);
			$questionManager->computeAnswerCounters($question);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createAnswerActivity($answer, false);

			// Dispatch publication event (on Question)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CHANGED, new PublicationEvent($question));

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::NAME);
			$watchableUtils->autoCreateWatch($question, $this->getUser());

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Qa/Answer:create-xhr.html.twig', array(
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
	 * @Route("/answer/{id}/edit", requirements={"id" = "\d+"}, name="core_qa_answer_edit")
	 * @Template("LadbCoreBundle:Qa/Answer:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
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
	 * @Route("/answer/{id}/update", requirements={"id" = "\d+"}, name="core_qa_answer_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Qa/Answer:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
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

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($answer, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($answer);

			if ($answer->getUser()->getId() == $this->getUser()->getId()) {
				$answer->setUpdatedAt(new \DateTime());
			}

			$om->flush();

			// Dispatch publication event (on Question)
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($answer->getQuestion()));

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Qa/Answer:_row.part.html.twig', array(
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
	 * @Route("/answer/{id}/best/create", requirements={"id" = "\d+"}, defaults={"action" = "create"}, name="core_qa_answer_best_create")
	 * @Route("/answer/{id}/best/delete", requirements={"id" = "\d+"}, defaults={"action" = "delete"}, name="core_qa_answer_best_delete")
	 */
	public function bestToggleAction(Request $request, $id, $action) {
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
		$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($question));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )).'#_answer_'.$answer->getId());
	}

	/**
	 * @Route("/answer/{id}/delete", requirements={"id" = "\d+"}, name="core_qa_answer_delete")
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Article entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $answer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_qa_answer_delete)');
		}

		$question = $answer->getQuestion();

		// Delete
		$answerManager = $this->get(AnswerManager::NAME);
		$answerManager->delete($answer);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('qa.answer.form.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/answers", requirements={"id" = "\d+"}, name="core_qa_answer_list")
	 * @Route("/{id}/answers/{sorter}", requirements={"id" = "\d+", "sorter" = "[a-z-]+"}, name="core_qa_answer_list_sorter")
	 * @Template("LadbCoreBundle:Qa/Answer:list-xhr.html.twig")
	 */
	public function listAction(Request $request, $id, $sorter = 'score') {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Qa\Question::CLASS_NAME);
		$answerRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Qa\Answer::CLASS_NAME);

		$id = intval($id);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$answers = $answerRepository->findByQuestion($question, $sorter);

		$commentableUtils = $this->get(CommentableUtils::NAME);
		$votableUtils = $this->get(VotableUtils::NAME);

		return array(
			'sorter'          => $sorter,
			'question'        => $question,
			'answers'         => $answers,
			'voteContexts'    => $votableUtils->getVoteContexts($question->getAnswers(), $this->getUser()),
			'commentContexts' => $commentableUtils->getCommentContexts($question->getAnswers(), false),
		);
	}

	/**
	 * @Route("/{id}/admin/converttocomment", requirements={"id" = "\d+"}, name="core_qa_answer_admin_converttocomment")
	 */
	public function adminConvertToCommentAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Answer entity (id='.$id.').');
		}
		$question = $answer->getQuestion();

		// Convert
		$answerManager = $this->get(AnswerManager::NAME);
		$find = $answerManager->convertToComment($answer, $question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('qa.answer.admin.alert.converttocomment_success', array( '%title%' => $answer->getTitle() )));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

}
