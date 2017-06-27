<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Activity\AbstractActivity;
use Ladb\CoreBundle\Entity\Core\Follower;
use Ladb\CoreBundle\Entity\Core\Notification;
use Ladb\CoreBundle\Entity\Core\Watch;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Ladb\CoreBundle\Utils\TypableUtils;

class CronNotificationPopulateCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:notification:populate')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Process activities to populate notifications')
			->setHelp(<<<EOT
The <info>ladb:cron:notification:populate</info> process activities to populate notifications
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$activityRepository = $om->getRepository(AbstractActivity::CLASS_NAME);
		$watchRepository = $om->getRepository(Watch::CLASS_NAME);
		$typableUtils = $this->getContainer()->get(TypableUtils::NAME);

		$notifiedUsers = array();
		$freshNotificationCounters = array();
		$activites = $activityRepository->findByPendingNotifications();
		if ($verbose) {
			$output->writeln('<info>'.count($activites).' activities to process...</info>');
		}
		foreach ($activites as $activity) {

			$actorUser = $activity->getUser();

			// Comment /////

			if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Comment) {

				$comment = $activity->getComment();
				$publication = $typableUtils->findTypable($comment->getEntityType(), $comment->getEntityId());
				if ($publication instanceof WatchableInterface || $publication instanceof WatchableChildInterface) {

					if ($publication instanceof WatchableChildInterface) {
						$watchable = $typableUtils->findTypable($publication->getParentEntityType(), $publication->getParentEntityId());
					} else {
						$watchable = $publication;
					}

					if ($watchable->getWatchCount() > 0) {

						$watches = $watchRepository->findByEntityTypeAndEntityIdExcludingUser($watchable->getType(), $watchable->getId(), $actorUser);
						if (!is_null($watches)) {
							foreach ($watches as $watch) {
								$this->_createNotification($om, $watch->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
								if ($verbose) {
									$output->writeln('<info>--> Notifying <fg=white>@'.$watch->getUser()->getUsername(). '</fg=white> for new comment='.mb_strimwidth($comment->getBody(), 0, 50, '[...]').' on='.$watchable->getTitle().'</info>');
								}
							}
						}

					}

				}

			}

			// Contribute /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Contribute) {

				// No Notification

			}

			// Follow /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Follow) {

				$follower = $activity->getFollower();

				// Notification
				$this->_createNotification($om, $follower->getFollowingUser(), $activity, $notifiedUsers, $freshNotificationCounters);
				if ($verbose) {
					$output->writeln('<info>--> Notifying <fg=white>@'.$follower->getFollowingUser()->getUsername(). '</fg=white> for new follower=@'.$actorUser->getUsername().'</info>');
				}

			}

			// Like /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Like) {

				$like = $activity->getLike();
				$publication = $typableUtils->findTypable($like->getEntityType(), $like->getEntityId());
				if ($publication instanceof AbstractAuthoredPublication) {
					$this->_createNotification($om, $publication->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
					if ($verbose) {
						$output->writeln('<info>--> Notifying <fg=white>@'.$publication->getUser()->getUsername(). '</fg=white> for new like from=@'.$actorUser->getUsername().' on='.$publication->getTitle().'</info>');
					}
				}

			}

			// Mention /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Mention) {

				// TODO

			}

			// Publish /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Publish) {

				$publication = $typableUtils->findTypable($activity->getEntityType(), $activity->getEntityId());
				if (!is_null($publication)) {
					$notificationStrategy = $publication->getNotificationStrategy();

					$excludedUserIds = array();

					// Follower strategy
					if ($notificationStrategy & PublicationInterface::NOTIFICATION_STRATEGY_FOLLOWER == PublicationInterface::NOTIFICATION_STRATEGY_FOLLOWER) {

						if ($actorUser->getFollowerCount() >= 0 && $publication instanceof TitledInterface) {

							$followerRepository = $om->getRepository(Follower::CLASS_NAME);
							$followers = $followerRepository->findByFollowingUser($actorUser);
							if (!is_null($followers)) {
								foreach ($followers as $follower) {
									$this->_createNotification($om, $follower->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
									$excludedUserIds[] = $follower->getUser()->getId();
									if ($verbose) {
										$output->writeln('<info>--> Notifying <fg=white>@'.$follower->getUser()->getUsername(). '</fg=white> for new publication='.$publication->getTitle().' (follower)</info>');
									}
								}
							}

						}

					}

					// Watch strategy
					if ($notificationStrategy & PublicationInterface::NOTIFICATION_STRATEGY_WATCH == PublicationInterface::NOTIFICATION_STRATEGY_WATCH) {

						if ($publication instanceof WatchableInterface) {
							$watchable = $publication;
						} else if ($publication instanceof WatchableChildInterface) {
							$watchable = $typableUtils->findTypable($publication->getParentEntityType(), $publication->getParentEntityId());
						}

						if ($watchable->getWatchCount() > 0 && $publication instanceof TitledInterface) {

							$watches = $watchRepository->findByEntityTypeAndEntityIdExcludingUser($watchable->getType(), $watchable->getId(), $actorUser);
							if (!is_null($watches)) {
								foreach ($watches as $watch) {
									if (in_array($watch->getUser()->getId(), $excludedUserIds)) {
										continue;
									}
									$this->_createNotification($om, $watch->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
									if ($verbose) {
										$output->writeln('<info>--> Notifying <fg=white>@'.$watch->getUser()->getUsername(). '</fg=white> for new publication='.$publication->getTitle().' (watch)</info>');
									}
								}
							}

						}

					}

				}

			}

			// Vote /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Vote) {

				$vote = $activity->getVote();
				$voteEntity = $typableUtils->findTypable($vote->getEntityType(), $vote->getEntityId());
				$this->_createNotification($om, $voteEntity->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
				if ($verbose) {
					$output->writeln('<info>--> Notifying <fg=white>@'.$voteEntity->getUser()->getUsername(). '</fg=white> for new vote from=@'.$actorUser->getUsername().'</info>');
				}

			}

			// Join /////

			else if ($activity instanceof \Ladb\CoreBundle\Entity\Activity\Join) {

				$join = $activity->getJoin();
				$joinEntity = $typableUtils->findTypable($join->getEntityType(), $join->getEntityId());

				if ($joinEntity instanceof WatchableInterface && $joinEntity->getWatchCount() > 0 && $joinEntity instanceof TitledInterface) {

					$watches = $watchRepository->findByEntityTypeAndEntityIdExcludingUser($joinEntity->getType(), $joinEntity->getId(), $actorUser);
					if (!is_null($watches)) {
						foreach ($watches as $watch) {
							if ($watch->getUser()->getId() == $join->getUser()->getId()) {
								continue;
							}
							$this->_createNotification($om, $watch->getUser(), $activity, $notifiedUsers, $freshNotificationCounters);
							if ($verbose) {
								$output->writeln('<info>--> Notifying <fg=white>@'.$watch->getUser()->getUsername(). '</fg=white> for new join from=@'.$actorUser->getUsername().'</info>');
							}
						}
					}

				}

			}

			// Flag activity as notified
			$activity->setIsPendingNotifications(false);

		}

		if ($forced) {
			$om->flush();
		}

		// Update fresh notification counters
		foreach ($notifiedUsers as $userId => $user) {
			$user->incrementFreshNotificationCount($freshNotificationCounters[$userId]);
			if ($verbose) {
				$output->writeln('<info>'.$user->getDisplayname().' <fg=yellow>['.$user->getFreshNotificationCount().' fresh ('.$freshNotificationCounters[$userId].' new)]</fg=yellow></info>');
			}
		}

		if ($forced) {
			$om->flush();
		}

	}

	/////

	private function _createNotification($om, $user, $activity, &$notifiedUsers, &$freshNotificationCount) {

		$notification = new Notification();
		$notification->setUser($user);
		$notification->setActivity($activity);

		$notifiedUsers[$user->getId()] = $user;
		if (isset($freshNotificationCount[$user->getId()])) {
			$freshNotificationCount[$user->getId()]++;
		} else {
			$freshNotificationCount[$user->getId()] = 1;
		}

		$om->persist($notification);
	}

}