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
class MessageController extends Controller {

	/**
	 * @Route("/thread/new", name="core_message_thread_new", defaults={"recipientUsername" = null, "announcement" = false})
	 * @Route("/thread/announcement/new", name="core_message_thread_new_announcement", defaults={"recipientUsername" = null, "announcement" = true})
	 * @Route("/thread/{recipientUsername}/new", name="core_message_thread_new_recipientusername", defaults={"announcement" = false})
	 * @Template("LadbCoreBundle:Message:newThread.html.twig")
	 */
	public function newThreadAction($recipientUsername, $announcement) {
		if ($announcement && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_new_announcement)');
		}

		$newThreadMessage = $announcement ? new NewThreadAnnouncementMessage() : new NewThreadMessage();

		if (!$announcement && !is_null($recipientUsername)) {
			$userManager = $this->get('fos_user.user_manager');
			$recipient = $userManager->findUserByUsername($recipientUsername);
			if (is_null($recipient)) {
				throw $this->createNotFoundException('User not found (core_message_thread_new_recipientusername)');
			}
			$newThreadMessage->addRecipient($recipient);
		}

		$form = $this->createForm($announcement ? NewThreadAnnouncementMessageType::class : NewThreadMessageType::class, $newThreadMessage);

		return array(
			'form'         => $form->createView(),
			'announcement' => $announcement,
		);
	}

	/**
	 * @Route("/thread/create", methods={"POST"}, name="core_message_thread_create")
	 * @Template("LadbCoreBundle:Message:newThread.html.twig")
	 */
	public function createThreadAction(Request $request) {

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
	 * @Route("/thread/announcement/create", methods={"POST"}, name="core_message_thread_create_announcement")
	 * @Template("LadbCoreBundle:Message:newThread.html.twig")
	 */
	public function createThreadAnnouncementAction(Request $request) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_create_announcement)');
		}

		$newThreadAnnouncementMessage = new NewThreadAnnouncementMessage();
		$form = $this->createForm(NewThreadAnnouncementMessageType::class, $newThreadAnnouncementMessage);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$sender = $this->getUser();

			$om = $this->getDoctrine()->getManager();
			$userRepository = $om->getRepository(User::CLASS_NAME);
			$recipients = $userRepository->findAll();

			$messageUtils = $this->get(MessageUtils::NAME);
			$mailerUtils = $this->get(MailerUtils::NAME);

			foreach ($recipients as $recipient) {

				if ($recipient->getId() == $sender->getId()) {
					continue;
				}

				// Compose thread
				$thread = $messageUtils->composeThread($sender, array( $recipient), $newThreadAnnouncementMessage->getSubject(), $newThreadAnnouncementMessage->getBody(), null, true );

				// Email notification
				$mailerUtils->sendIncomingMessageNotificationEmailMessage($recipient, $sender, $thread, $thread->getMessages()->last());

			}

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', 'Votre message a été envoyé à tout le monde');

            return $this->redirect($this->generateUrl('core_message_mailbox'));
		}

		return array(
			'form'         => $form->createView(),
			'announcement' => true,
		);
	}

	/**
	 * @Route("/thread/{id}/delete", requirements={"id" = "\d+"}, name="core_message_thread_delete")
	 * @Template("LadbCoreBundle:Message:deleteThread.html.twig")
	 */
	public function deleteThreadAction($id) {
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
	 * @Route("/thread/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_message_thread_update")
	 * @Template("LadbCoreBundle:Message:showThread.html.twig")
	 */
	public function updateThreadAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);

		$thread = $threadRepository->findOneByIdJoinedOnMetaAndParticipant($id);
		if (is_null($thread)) {
			throw $this->createNotFoundException('Unable to find Thread entity (id='.$id.').');
		}
		if (!$thread->getParticipants()->contains($this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_message_thread_update)');
		}

		$replyMessage = new ReplyMessage();
		$form = $this->createForm(ReplyMessageType::class, $replyMessage);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);

			$sender = $this->getUser();
			$participants = $thread->getParticipants();
			$recipients = array();

			$thread->setLastMessageDate(new \DateTime());

			$message = new Message();
			$message->setSender($sender);
			$message->setBody($replyMessage->getBody());
			foreach ($replyMessage->getPictures() as $picture) {
				$message->addPicture($picture);
			}
			$fieldPreprocessorUtils->preprocessBodyField($message);
			$thread->addMessage($message);

			foreach ($participants as $participant) {
				$messageMeta = new MessageMeta();
				$messageMeta->setParticipant($participant);
				$messageMeta->setIsRead(false);
				$message->addMeta($messageMeta);

				$participant->getMeta()->incrementUnreadMessageCount();

				if ($participant != $sender) {
					$recipients[] = $participant;
				}
			}

			foreach ($thread->getMetas() as $threadMeta) {
				$threadMeta->setIsDeleted(false);
			}

			$om->flush();

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

			return $this->redirect($this->generateUrl('core_message_thread_show', array('id' => $thread->getId())));
		}

		return array(
			'thread' => $thread,
			'form'   => $form->createView(),
		);
	}

	/**
	 * @Route("/thread/{id}", requirements={"id" = "\d+"}, name="core_message_thread_show")
	 * @Template("LadbCoreBundle:Message:showThread.html.twig")
	 */
	public function showThreadAction($id) {
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

		// Reply form

		$replyMessage = new ReplyMessage();
		$form = $this->createForm(ReplyMessageType::class, $replyMessage);

		return array(
			'thread' => $thread,
			'form'   => $form->createView(),
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
