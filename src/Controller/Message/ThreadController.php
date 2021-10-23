<?php

namespace App\Controller\Message;

use App\Entity\Message\MessageMeta;
use App\Fos\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Core\Member;
use App\Entity\Message\Thread;
use App\Utils\PaginatorUtils;
use App\Utils\MessageUtils;
use App\Form\Type\Message\NewThreadMessageType;
use App\Form\Model\NewThreadMessage;
use App\Utils\MailerUtils;

/**
 * @Route("/messagerie")
 */
class ThreadController extends AbstractThreadController {

	/**
	 * @Route("/thread/new", name="core_message_thread_new", defaults={"recipientUsername" = null, "announcement" = false})
	 * @Route("/thread/to/{recipientUsername}/new", requirements={"recipientUsername" = "[a-zA-Z0-9]+"}, name="core_message_thread_new_recipientusername", defaults={"announcement" = false})
	 * @Template("Message/newThread.html.twig")
	 */
	public function new($recipientUsername, $subject = null, $message = null, $alertTemplate =  null) {
		if (!is_null($this->getUser()) && $this->getUser()->getIsTeam()) {
			throw $this->createNotFoundException('Team not allowed (core_message_thread_new)');
		}

		$newThreadMessage = new NewThreadMessage();

		if (!is_null($recipientUsername)) {
			$userManager = $this->get(UserManager::class);
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
			'form'          => $form->createView(),
			'alertTemplate' => $alertTemplate,
		);
	}

	/**
	 * @Route("/thread/create", methods={"POST"}, name="core_message_thread_create")
	 * @Template("Message/newThread.html.twig")
	 */
	public function create(Request $request) {
		if (!is_null($this->getUser()) && $this->getUser()->getIsTeam()) {
			throw $this->createNotFoundException('Team not allowed (core_message_thread_create)');
		}

		$this->createLock('core_message_thread_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$newThreadMessage = new NewThreadMessage();
		$form = $this->createForm(NewThreadMessageType::class, $newThreadMessage);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$sender = $this->getUser();
			$recipients = $newThreadMessage->getRecipients()->toArray();

			// Exclude "SPAM" threads
			$om = $this->getDoctrine()->getManager();
			$threadRepository = $om->getRepository(Thread::CLASS_NAME);
			if ($threadRepository->existsBySenderAndSubjectAndBody($sender, $newThreadMessage->getSubject(), $newThreadMessage->getBody())) {

				// Email notification
				$mailerUtils = $this->get(MailerUtils::class);
				$mailerUtils->sendSpamThreadNotificationEmailMessage($sender, $recipients, $newThreadMessage->getSubject(), $newThreadMessage->getBody());

				throw $this->createNotFoundException('SPAM thread detected (sender='.$sender->getUsername().', subject='.$newThreadMessage->getSubject().').');
			}

			$messageUtils = $this->get(MessageUtils::class);
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
			$this->notifyRecipientsForIncomingMessage($recipients, $sender, $thread, $thread->getMessages()->last());

			return $this->redirect($this->generateUrl('core_message_thread_show', array( 'id' => $thread->getId())));
		}

		return array(
			'form'         => $form->createView(),
			'announcement' => false,
		);
	}

	/**
	 * @Route("/thread/{id}/delete", requirements={"id" = "\d+"}, name="core_message_thread_delete")
	 * @Template("Message/deleteThread.html.twig")
	 */
	public function delete($id) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);

		$thread = $this->retrieveThread($id);
		$this->assertDeletable($thread);

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
	 * @Template("Message/showThread.html.twig")
	 */
	public function show($id) {

		$this->createLock('core_message_thread_show', true, 3, false);

		$om = $this->getDoctrine()->getManager();
		$messageMetaRepository = $om->getRepository(MessageMeta::CLASS_NAME);

		$thread = $this->retrieveThread($id);
		$this->assertShowable($thread);

		// Flag messages as read

		$participant = $this->getUser();

		$unreadMessageCount = 0;
		foreach ($thread->getMessages() as $message) {

			$messageMeta = $messageMetaRepository->findOneByMessageAndParticipant($message, $participant);
			if (is_null($messageMeta)) {

				$message->setIsRead(false);	// used to view only

				$messageMeta = new MessageMeta();
				$messageMeta->setParticipant($participant);
				$messageMeta->setIsRead(true);
				$message->addMeta($messageMeta);

				$om->persist($messageMeta);

				$unreadMessageCount++;
			} else {
				if (!$messageMeta->getIsRead()) {
					$messageMeta->setIsRead(true);
					$unreadMessageCount++;
				}
			}

		}

		if ($unreadMessageCount > 0) {

			// Decrement unread message count
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
	 * @Template("Message/mailbox.html.twig")
	 */
	public function mailbox(Request $request, $filter = 'all', $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$threadRepository = $om->getRepository(Thread::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		// Compute allowed user list by adding user's teams
		$users = array( $this->getUser() );
		if ($this->getUser()->getMeta()->getTeamCount() > 0) {
			$memberRepository = $om->getRepository(Member::CLASS_NAME);
			$members = $memberRepository->findPaginedByUser($this->getUser());
			foreach ($members as $member) {
				$users[] = $member->getTeam();
			}
		}

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $threadRepository->findPaginedByUsers($users, $offset, $limit, $filter);
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
			return $this->render('Message:mailbox-xhr.html.twig', $parameters);
		}
		return $parameters;
	}

}
