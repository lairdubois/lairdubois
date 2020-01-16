<?php

namespace Ladb\CoreBundle\Controller\Message;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Utils\WebpushNotificationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Message\Message;
use Ladb\CoreBundle\Entity\Message\MessageMeta;
use Ladb\CoreBundle\Entity\Message\Thread;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\MessageUtils;
use Ladb\CoreBundle\Form\Type\Message\NewThreadMessageType;
use Ladb\CoreBundle\Form\Type\Message\NewThreadAnnouncementMessageType;
use Ladb\CoreBundle\Form\Type\Message\ReplyMessageType;
use Ladb\CoreBundle\Form\Model\NewThreadMessage;
use Ladb\CoreBundle\Form\Model\NewThreadAnnouncementMessage;
use Ladb\CoreBundle\Form\Model\ReplyMessage;
use Ladb\CoreBundle\Utils\MailerUtils;

/**
 * @Route("/messagerie")
 */
class MessageController extends AbstractController {

	private function _retrieveThread($threadId) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);
		$thread = $threadRepository->findOneByIdJoinedOnMetaAndParticipant($threadId);
		if (is_null($thread)) {
			throw $this->createNotFoundException('Unable to find Thread entity (id='.$id.').');
		}
		if (!$thread->getParticipants()->contains($this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_update)');
		}
		return $thread;
	}

	/**
	 * @Route("/thread/{threadId}/new", requirements={"threadId" = "\d+"}, name="core_message_new")
	 * @Template("LadbCoreBundle:Message:new-xhr.html.twig")
	 */
	public function newAction(Request $request, $threadId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		// Retrieve related thread
		$thread = $this->_retrieveThread($threadId);

		$message = new Message();
		$form = $this->createForm(ReplyMessageType::class, $message);

		return array(
			'thread' => $thread,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/thread/{threadId}/create", requirements={"threadId" = "\d+"}, name="core_message_create")
	 * @Template("LadbCoreBundle:Message:new-xhr.html.twig")
	 */
	public function createAction(Request $request, $threadId) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$this->createLock('core_message_create');

		$thread = $this->_retrieveThread($threadId);

		$message = new Message();
		$form = $this->createForm(ReplyMessageType::class, $message);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om = $this->getDoctrine()->getManager();
			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);

			$sender = $this->getUser();
			$participants = $thread->getParticipants();
			$recipients = array();

			$thread->setLastMessageDate(new \DateTime());

			$message->setSender($sender);
			$fieldPreprocessorUtils->preprocessBodyField($message);
			$thread->addMessage($message);

			foreach ($participants as $participant) {

				$messageMeta = new MessageMeta();
				$messageMeta->setParticipant($participant);
				$messageMeta->setIsRead($participant == $sender);
				$message->addMeta($messageMeta);

				if ($participant != $sender) {

					// Increment unread message count
					$participant->getMeta()->incrementUnreadMessageCount();

					// Populate recipients list
					$recipients[] = $participant;

				}
			}

			// Reactivate deleted threads
			foreach ($thread->getMetas() as $threadMeta) {
				$threadMeta->setIsDeleted(false);
			}

			$om->flush();

			if (!$sender->getEmailConfirmed() && $sender->getMeta()->getIncomingMessageEmailNotificationEnabled()) {
				$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.alert.email_not_confirmed_error'));
			}

			// Notifications (after flush to have a thread id)
			$mailerUtils = $this->get(MailerUtils::NAME);
			$webpushNotificationUtils = $this->get(WebpushNotificationUtils::class);
			foreach ($recipients as $recipient) {

				// Email notification
				$mailerUtils->sendIncomingMessageNotificationEmailMessage($recipient, $sender, $thread, $thread->getMessages()->last());

				// Webpush notification
				$webpushNotificationUtils->enqueueIncomingMessageNotification($recipient, $sender, $thread);

			}

			return $this->render('LadbCoreBundle:Message:create-xhr.html.twig', array( 'message' => $message ));
		}

		return array(
			'thread' => $thread,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_message_edit")
	 * @Template("LadbCoreBundle:Message:edit-xhr.html.twig")
	 */
	public function editAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$messageRepository = $om->getRepository(Message::CLASS_NAME);

		$message = $messageRepository->findOneById($id);
		if (is_null($message)) {
			throw $this->createNotFoundException('Unable to find Message entity (id='.$id.').');
		}
		if ($message->getSender() != $this->getUser()) {
			throw $this->createNotFoundException('Not allowed (core_message_edit)');
		}

		$form = $this->createForm(ReplyMessageType::class, $message);

		return array(
			'message' => $message,
			'form'    => $form->createView(),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_message_update")
	 * @Template("LadbCoreBundle:Message:edit-xhr.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$messageRepository = $om->getRepository(Message::CLASS_NAME);

		$message = $messageRepository->findOneById($id);
		if (is_null($message)) {
			throw $this->createNotFoundException('Unable to find Message entity (id='.$id.').');
		}
		if ($message->getSender() != $this->getUser()) {
			throw $this->createNotFoundException('Not allowed (core_message_edit)');
		}

		$form = $this->createForm(ReplyMessageType::class, $message);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$om = $this->getDoctrine()->getManager();
			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);

			$fieldPreprocessorUtils->preprocessBodyField($message);

			$om->flush();

			return $this->render('LadbCoreBundle:Message:update-xhr.html.twig', array('message' => $message));
		}

		return array(
			'message' => $message,
			'form'    => $form->createView(),
		);
	}

}
