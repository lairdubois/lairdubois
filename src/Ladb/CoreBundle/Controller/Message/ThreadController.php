<?php

namespace Ladb\CoreBundle\Controller\Message;

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
class ThreadController extends Controller {

	/**
	 * @Route("/thread/new", name="core_message_thread_new", defaults={"recipientUsername" = null, "announcement" = false})
	 * @Route("/thread/to/{recipientUsername}/new", requirements={"recipientUsername" = "[a-zA-Z0-9]+"}, name="core_message_thread_new_recipientusername", defaults={"announcement" = false})
	 * @Template("LadbCoreBundle:Message:newThread.html.twig")
	 */
	public function newAction($recipientUsername, $subject = null, $message = null) {

		$newThreadMessage = new NewThreadMessage();

		if (!is_null($recipientUsername)) {
			$userManager = $this->get('fos_user.user_manager');
			$recipient = $userManager->findUserByUsername($recipientUsername);
			if (is_null($recipient)) {
				throw $this->createNotFoundException('User not found (core_message_thread_new_recipientusername)');
			}
			$newThreadMessage->addRecipient($recipient);
		}

		if (!is_null($subject)) {
			$newThreadMessage->setSubject($subject);
		}

		if (!is_null($message)) {
			$newThreadMessage->setBody($message);
		}

		$form = $this->createForm(NewThreadMessageType::class, $newThreadMessage);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/thread/create", methods={"POST"}, name="core_message_thread_create")
	 * @Template("LadbCoreBundle:Message:newThread.html.twig")
	 */
	public function createAction(Request $request) {

		$newThreadMessage = new NewThreadMessage();
		$form = $this->createForm(NewThreadMessageType::class, $newThreadMessage);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$sender = $this->getUser();
			$recipients = $newThreadMessage->getRecipients()->toArray();

			$messageUtils = $this->get(MessageUtils::NAME);
			$thread = $messageUtils->composeThread($sender, $recipients, $newThreadMessage->getSubject(), $newThreadMessage->getBody(), $newThreadMessage->getPictures());

			// Flashbag
			$recipientNames = '';
			foreach ($recipients as $recipient) {
				if (strlen($recipientNames) != 0) {
					$recipientNames .= ', ';
				}
				$recipientNames .= $recipient->getDisplayname();
			}
			$this->get('session')->getFlashBag()->add('success', 'Votre message a été envoyé à <strong>'.$recipientNames.'</strong>.');

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

			return $this->redirect($this->generateUrl('core_message_thread_show', array( 'id' => $thread->getId())));
		}

		return array(
			'form'         => $form->createView(),
			'announcement' => false,
		);
	}

	/**
	 * @Route("/thread/{id}/delete", requirements={"id" = "\d+"}, name="core_message_thread_delete")
	 * @Template("LadbCoreBundle:Message:deleteThread.html.twig")
	 */
	public function deleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);

		$thread = $threadRepository->findOneByIdJoinedOnMetaAndParticipant($id);
		if (is_null($thread)) {
			throw $this->createNotFoundException('Unable to find Thread entity (id='.$id.').');
		}
		if (!$thread->getParticipants()->contains($this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_delete)');
		}

		$unreadMessageCount = $threadRepository->countUnreadMessageByThreadAndUser($thread, $this->getUser());
		if ($unreadMessageCount > 0) {
			$this->getUser()->getMeta()->incrementUnreadMessageCount(-$unreadMessageCount);
		}

		$remainingCount = 0;
		foreach ($thread->getMetas() as $threadMeta) {
			if ($threadMeta->getParticipant() === $this->getUser()) {
				$threadMeta->setIsDeleted(true);
			} else if (!$threadMeta->getIsDeleted()) {
				$remainingCount++;
			}
		}

		if ($remainingCount == 0) {
			$om->remove($thread);
		}
		$om->flush();

		return $this->redirect($this->generateUrl('core_message_mailbox'));
	}

	/**
	 * @Route("/thread/{id}", requirements={"id" = "\d+"}, name="core_message_thread_show")
	 * @Template("LadbCoreBundle:Message:showThread.html.twig")
	 */
	public function showAction($id) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);

		$thread = $threadRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($thread)) {
			throw $this->createNotFoundException('Unable to find Thread entity (id='.$id.').');
		}
		if (!$thread->getParticipants()->contains($this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_show)');
		}

		// Flag messages as read

		$participant = $this->getUser();

		$unreadMessageCount = 0;
		foreach ($thread->getMessages() as $message) {
			foreach ($message->getMetas() as $messageMeta) {
				if (!$messageMeta->getIsRead() && $messageMeta->getParticipant() === $this->getUser()) {
					$message->setIsRead(false);	// used to view only
					$messageMeta->setIsRead(true);
					$unreadMessageCount++;
				}
			}
		}

		if ($unreadMessageCount > 0) {
			$participant->getMeta()->incrementUnreadMessageCount(-$unreadMessageCount);

			$om->flush();

		}

		return array(
			'thread' => $thread,
		);
	}

	/**
	 * @Route("/", name="core_message_mailbox")
	 * @Route("/{filter}", requirements={"filter" = "\w+"}, name="core_message_mailbox_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_message_mailbox_filter_page")
	 * @Template("LadbCoreBundle:Message:mailbox.html.twig")
	 */
	public function mailboxAction(Request $request, $filter = 'all', $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $threadRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_message_mailbox_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		// Compute unreadMessageCount

		$threads = $paginator;

		$participant = $this->getUser();
		foreach ($threads as $thread) {
			$thread->setUnreadMessageCount($threadRepository->countUnreadMessageByThreadAndUser($thread, $participant));
		}

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'threads'     => $threads,
			'threadCount' => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Message:mailbox-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

}
