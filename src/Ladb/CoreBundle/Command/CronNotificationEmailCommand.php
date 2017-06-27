<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Entity\Core\Notification;
use Ladb\CoreBundle\Model\WatchableChildInterface;

class CronNotificationEmailCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:notification:email')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Process notifications to send emails')
			->setHelp(<<<EOT
The <info>ladb:cron:notification:email</info> Process notifications to send emails
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$notificationRepository = $om->getRepository(Notification::CLASS_NAME);

		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Comment::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Contribute::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Follow::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Like::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Mention::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Publish::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Vote::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Join::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\Ladb\CoreBundle\Entity\Core\Activity\Write::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);

	}

	private function _processActivityByActivityStrippedName($activityStrippedName, $output, $forced, $verbose, $om, $notificationRepository) {

		$currentUser = null;
		$currentNotifications = array();

		$notifications = $notificationRepository->findByPendingEmailAndActivityInstanceOf('\\Ladb\\CoreBundle\\Entity\\Activity\\'.ucfirst($activityStrippedName));
		if ($verbose) {
			$output->writeln('<info>'.count($notifications).' notifications ('.$activityStrippedName.') to process...</info>');
		}
		foreach ($notifications as $notification) {

			if (is_null($currentUser)) {
				$currentUser = $notification->getUser();
			}
			if ($notification->getUser()->getId() == $currentUser->getId()) {
				$currentNotifications[] = $notification;
			} else {
				$this->_processUserNotifications($currentUser, $currentNotifications, $activityStrippedName, $output, $forced, $verbose, $om);
				$currentUser = $notification->getUser();
				$currentNotifications = array( $notification );
			}

		}
		if (!is_null($currentUser)) {
			$this->_processUserNotifications($currentUser, $currentNotifications, $activityStrippedName, $output, $forced, $verbose, $om);
		}
	}

	private function _processUserNotifications(User $recipientUser, $notifications, $activityStrippedName, $output, $forced, $verbose, $om) {
		if ($this->_isNotificationEnabledByActivityStrippedName($recipientUser, $activityStrippedName) && $recipientUser->getEmailConfirmed()) {

			$typableUtils = $this->getContainer()->get(TypableUtils::NAME);
			$mailerUtils = $this->getContainer()->get(MailerUtils::NAME);
			$translator = $this->getContainer()->get('translator');
			$templating = $this->getContainer()->get('templating');

			$rows = array();

			foreach ($notifications as $notification) {

				$activity = $notification->getActivity();

				$row = new \stdClass();
				$row->actorUser = $activity->getUser();

				// Comment
				if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Comment::STRIPPED_NAME) {

					$comment = $activity->getComment();
					$commentEntity = $typableUtils->findTypable($comment->getEntityType(), $comment->getEntityId());

					if ($commentEntity instanceof WatchableChildInterface) {
						$row->entity = $typableUtils->findTypable($commentEntity->getParentEntityType(), $commentEntity->getParentEntityId());
						$row->childEntity = $commentEntity;
					} else {
						$row->entity = $commentEntity;
						$row->childEntity = null;
					}
					$row->comment = $comment;

				}

				// Contribute
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Contribute::STRIPPED_NAME) {

					// TODO

				}

				// Follow
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Follow::STRIPPED_NAME) {

					$follower = $activity->getFollower();
					$row->follower = $follower;

				}

				// Like
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Like::STRIPPED_NAME) {

					$like = $activity->getLike();
					$row->entity = $typableUtils->findTypable($like->getEntityType(), $like->getEntityId());
					$row->like = $like;

				}

				// Mention
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Mention::STRIPPED_NAME) {

					// TODO

				}

				// Publish
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Publish::STRIPPED_NAME) {

					$row->entity = $typableUtils->findTypable($activity->getEntityType(), $activity->getEntityId());

				}

				// Vote
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Vote::STRIPPED_NAME) {

					$vote = $activity->getVote();
					$row->entity = $typableUtils->findTypable($vote->getParentEntityType(), $vote->getParentEntityId());
					$row->activityEntity = $typableUtils->findTypable($vote->getEntityType(), $vote->getEntityId());
					$row->vote = $vote;

				}

				// Join
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Join::STRIPPED_NAME) {

					$join = $activity->getJoin();
					$row->entity = $typableUtils->findTypable($join->getEntityType(), $join->getEntityId());
					$row->join = $join;

				}

				// Write
				else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Write::STRIPPED_NAME) {

					$message = $activity->getMessage();

					// TODO

				}

				// Unknow
				else {
					break;
				}

				$rows[] = $row;

			}

			$parameters = array(
				'recipientUser' => $recipientUser,
				'rows'          => $rows,
			);

			$subject = $translator->transChoice('notification.choice.'.$activityStrippedName, count($notifications));
			$body = $templating->render('LadbCoreBundle:Core/Notification:email-'.$activityStrippedName.'.txt.twig', $parameters);
			$htmlBody = $templating->render('LadbCoreBundle:Core/Notification:email-'.$activityStrippedName.'.html.twig', $parameters);

			if ($verbose) {
				$output->write('<info>--> Sending email to <fg=white>@'.$recipientUser->getDisplayname().'</fg=white> <fg=yellow>('.count($rows).' '.$activityStrippedName.')</fg=yellow>...</info>');
			}
			if ($forced) {
				$mailerUtils->sendEmailMessage($recipientUser->getEmail(), $subject, $body, $htmlBody);
				if ($verbose) {
					$output->writeln('<fg=cyan>[Done]</fg=cyan>');
				}
			} else {
				if ($verbose) {
					$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
				}
			}

		}

		// Flag notifications as emailed
		foreach ($notifications as $notification) {
			$notification->setIsPendingEmail(false);
		}
		if ($forced) {
			$om->flush();
		}

	}

	/////

	private function _isNotificationEnabledByActivityStrippedName(User $recipientUser, $activityStrippedName) {
		if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Comment::STRIPPED_NAME) {
			return $recipientUser->getNewWatchActivityEmailNotificationEnabled();
		}
		if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Contribute::STRIPPED_NAME) {
			return true;	// TODO
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Follow::STRIPPED_NAME) {
			return $recipientUser->getNewFollowerEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Like::STRIPPED_NAME) {
			return $recipientUser->getNewLikeEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Mention::STRIPPED_NAME) {
			return true;	// TODO
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Publish::STRIPPED_NAME) {
			return $recipientUser->getNewFollowingPostEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Vote::STRIPPED_NAME) {
			return $recipientUser->getNewVoteEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Join::STRIPPED_NAME) {
			return $recipientUser->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \Ladb\CoreBundle\Entity\Core\Activity\Write::STRIPPED_NAME) {
			return $recipientUser->getIncomingMessageEmailNotificationEnabled();
		}
		return true;
	}

}