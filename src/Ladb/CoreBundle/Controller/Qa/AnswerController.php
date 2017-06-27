<?php

namespace Ladb\CoreBundle\Controller\Qa;

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
 * @Route("/qa")
 */
class AnswerController extends Controller {

	/**
	 * @Route("/{id}/answer/new", requirements={"id" = "\d+"}, name="core_qa_answer_new")
	 * @Template()
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
	 * @Template("LadbCoreBundle:Qa/Answer:new.html.twig")
	 */
	public function createAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);

		$question = $questionRepository->findOneById($id);
		if (is_null($question)) {
			throw $this->createNotFoundException('Unable to find Question entity (id='.$id.').');
		}

		$answer = new Answer();
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
			$answer->setIsDraft(false);

			$question->addAnswer($answer);
			$question->incrementAnswerCount();

			$om->persist($answer);
			$om->flush();

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Qa/Answer:_row.part.html.twig', array(
				'answer'         => $answer,
				'commentContext' => $commentableUtils->getCommentContext($answer, $this->getUser()),
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
	 * @Template("LadbCoreBundle:Qa/Answer:edit.html.twig")
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
	 * @Template("LadbCoreBundle:Qa/Answer:edit.html.twig")
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

			$answer->setUpdatedAt(new \DateTime());

			$om->flush();

			$commentableUtils = $this->get(CommentableUtils::NAME);
			$votableUtils = $this->get(VotableUtils::NAME);

			return $this->render('LadbCoreBundle:Qa/Answer:_row.part.html.twig', array(
				'answer'         => $answer,
				'commentContext' => $commentableUtils->getCommentContext($answer, $this->getUser()),
				'voteContext'    => $votableUtils->getVoteContext($answer, $this->getUser()),
			));
		}

		return array(
			'answer' => $answer,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/answer/{id}/delete", requirements={"id" = "\d+"}, name="core_qa_answer_delete")
	 * @Template()
	 */
	public function deleteAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);

		$answer = $answerRepository->findOneById($id);
		if (is_null($answer)) {
			throw $this->createNotFoundException('Unable to find Article entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $answer->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_howto_article_delete)');
		}

		$question = $answer->getQuestion();

		// Delete
		$answerManager = $this->get(AnswerManager::NAME);
		$answerManager->delete($answer, true, false);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($question);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('qa.answer.form.alert.delete_success'));

		return $this->redirect($this->generateUrl('core_qa_question_show', array( 'id' => $question->getSluggedId() )));
	}

}
