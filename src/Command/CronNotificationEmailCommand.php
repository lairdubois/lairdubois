<?php

namespace App\Command;

use App\Entity\Core\Comment;
use App\Entity\Qa\Answer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\User;
use App\Entity\Core\Notification;
use App\Utils\MailerUtils;
use App\Utils\TypableUtils;
use App\Model\WatchableChildInterface;

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

		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Comment::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Contribute::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Follow::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Like::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Mention::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Publish::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Vote::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Join::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Write::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Answer::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Testify::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Review::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Feedback::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Invite::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);
		$this->_processActivityByActivityStrippedName(\App\Entity\Core\Activity\Request::STRIPPED_NAME, $output, $forced, $verbose, $om, $notificationRepository);

	}

	private function _processActivityByActivityStrippedName($activityStrippedName, $output, $forced, $verbose, $om, $notificationRepository) {

		$currentUser = null;
		$currentNotifications = array();

		$notifications = $notificationRepository->findByPendingEmailAndActivityInstanceOf('App\\Entity\\Core\\Activity\\'.ucfirst($activityStrippedName));
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

			$typableUtils = $this->getContainer()->get(TypableUtils::class);
			$mailerUtils = $this->getContainer()->get(MailerUtils::class);
			$translator = $this->getContainer()->get('translator');
			$templating = $this->getContainer()->get('templating');

			$rows = array();

			foreach ($notifications as $notification) {

				$activity = $notification->getActivity();

				$row = new \stdClass();
				$row->actorUser = $activity->getUser();

				// Comment
				if ($activityStrippedName == \App\Entity\Core\Activity\Comment::STRIPPED_NAME) {

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
				else if ($activityStrippedName == \App\Entity\Core\Activity\Contribute::STRIPPED_NAME) {

					// TODO

				}

				// Follow
				else if ($activityStrippedName == \App\Entity\Core\Activity\Follow::STRIPPED_NAME) {

					$follower = $activity->getFollower();
					$row->follower = $follower;

				}

				// Like
				else if ($activityStrippedName == \App\Entity\Core\Activity\Like::STRIPPED_NAME) {

					$like = $activity->getLike();
					$row->entity = $typableUtils->findTypable($like->getEntityType(), $like->getEntityId());
					$row->like = $like;

				}

				// Mention
				else if ($activityStrippedName == \App\Entity\Core\Activity\Mention::STRIPPED_NAME) {

					$mention = $activity->getMention();
					$row->entity = $typableUtils->findTypable($mention->getEntityType(), $mention->getEntityId());
					$row->activityEntity = $row->entity;
					if ($row->entity instanceof Comment) {
						$row->comment = $row->entity;
						$row->entity = $typableUtils->findTypable($row->comment->getEntityType(), $row->comment->getEntityId());
						if ($row->entity instanceof WatchableChildInterface) {
							$row->entity = $typableUtils->findTypable($row->entity->getParentEntityType(), $row->entity->getParentEntityId());
						}
					} else if ($row->entity instanceof Answer) {
						$row->answer = $row->entity;
						$row->entity = $row->answer->getQuestion();
					}
					$row->mention = $mention;

				}

				// Publish
				else if ($activityStrippedName == \App\Entity\Core\Activity\Publish::STRIPPED_NAME) {

					$row->entity = $typableUtils->findTypable($activity->getEntityType(), $activity->getEntityId());

				}

				// Vote
				else if ($activityStrippedName == \App\Entity\Core\Activity\Vote::STRIPPED_NAME) {

					$vote = $activity->getVote();
					$row->entity = $typableUtils->findTypable($vote->getParentEntityType(), $vote->getParentEntityId());
					$row->activityEntity = $typableUtils->findTypable($vote->getEntityType(), $vote->getEntityId());
					$row->vote = $vote;

				}

				// Join
				else if ($activityStrippedName == \App\Entity\Core\Activity\Join::STRIPPED_NAME) {

					$join = $activity->getJoin();
					$row->entity = $typableUtils->findTypable($join->getEntityType(), $join->getEntityId());
					$row->join = $join;

				}

				// Write
				else if ($activityStrippedName == \App\Entity\Core\Activity\Write::STRIPPED_NAME) {

					$message = $activity->getMessage();

					// TODO

				}

				// Answer
				else if ($activityStrippedName == \App\Entity\Core\Activity\Answer::STRIPPED_NAME) {

					$answer = $activity->getAnswer();
					$row->entity = $answer->getQuestion();
					$row->childEntity = null;
					$row->answer = $answer;

				}

				// Testify
				else if ($activityStrippedName == \App\Entity\Core\Activity\Testify::STRIPPED_NAME) {

					$testimonial = $activity->getTestimonial();
					$row->entity = $testimonial->getSchool();
					$row->childEntity = null;
					$row->testimonial = $testimonial;

				}

				// Review
				else if ($activityStrippedName == \App\Entity\Core\Activity\Review::STRIPPED_NAME) {

					$review = $activity->getReview();
					$row->entity = $typableUtils->findTypable($review->getEntityType(), $review->getEntityId());
					$row->childEntity = null;
					$row->review = $review;

				}

				// Feedback
				else if ($activityStrippedName == \App\Entity\Core\Activity\Feedback::STRIPPED_NAME) {

					$feedback = $activity->getFeedback();
					$row->entity = $typableUtils->findTypable($feedback->getEntityType(), $feedback->getEntityId());
					$row->childEntity = null;
					$row->feedback = $feedback;

				}

				// Invite
				else if ($activityStrippedName == \App\Entity\Core\Activity\Invite::STRIPPED_NAME) {

					$invitation = $activity->getInvitation();
					$row->invitation = $invitation;

				}

				// Request
				else if ($activityStrippedName == \App\Entity\Core\Activity\Request::STRIPPED_NAME) {

					$request = $activity->getRequest();
					$row->request = $request;

				}

				// Unknow
				else {
					break;
				}

				$rows[] = $row;

			}

			$parameters = array(
				'recipientUser'       => $recipientUser,
				'rows'                => $rows,
				'listUnsubscribeLink' => $mailerUtils->generateListUnsubscribeLink($recipientUser, MailerUtils::LIST_NOTIFICATIONS),
			);

			$subject = $translator->transChoice('notification.choice.'.$activityStrippedName, count($notifications));
			$body = $templating->render('Core/Notification:email-'.$activityStrippedName.'.txt.twig', $parameters);
			$htmlBody = $templating->render('Core/Notification:email-'.$activityStrippedName.'.html.twig', $parameters);

			if ($verbose) {
				$output->write('<info>--> Sending email to <fg=white>@'.$recipientUser->getDisplayname().'</fg=white> <fg=yellow>('.count($rows).' '.$activityStrippedName.')</fg=yellow>...</info>');
			}
			if ($forced) {
				$mailerUtils->sendEmailMessage(
					$recipientUser->getEmail(),
					$subject,
					$body,
					$htmlBody,
					$parameters['listUnsubscribeLink']
				);
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
		if ($activityStrippedName == \App\Entity\Core\Activity\Comment::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		if ($activityStrippedName == \App\Entity\Core\Activity\Contribute::STRIPPED_NAME) {
			return true;	// TODO
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Follow::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewFollowerEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Like::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewLikeEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Mention::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewMentionEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Publish::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewFollowingPostEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Vote::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewVoteEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Join::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Answer::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Testify::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Review::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Feedback::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getNewWatchActivityEmailNotificationEnabled();
		}
		else if ($activityStrippedName == \App\Entity\Core\Activity\Write::STRIPPED_NAME) {
			return $recipientUser->getMeta()->getIncomingMessageEmailNotificationEnabled();
		}
		return true;
	}

}