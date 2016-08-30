<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Entity\Spotlight;
use Ladb\CoreBundle\Utils\TypableUtils;

class CronSpotlightCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:spotlight')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->addOption('force-twitter', null, InputOption::VALUE_NONE, 'Force posting on Twitter')
			->addOption('force-facebook', null, InputOption::VALUE_NONE, 'Force posting on Facebook')
			->setDescription('Update spotlights')
			->setHelp(<<<EOT
The <info>ladb:cron:spotlight</info> update spotlights
EOT
			);
	}

	/////

	private function _publishOnTwitter($spotlight, $entity, $forced, $forcedTwitter, $verbose, $output) {

		$success = false;
		$status = $this->getContainer()->get('templating')->render('LadbCoreBundle:Command:_cron-spotlight-twitter-status.txt.twig', array( 'spotlight' => $spotlight, 'entity' => $entity));
		$mediaIds = '';
		if ($verbose) {
			$output->writeln('<info>Posting to Twitter (<fg=yellow>'.$status.'</fg=yellow>) ...</info>');
		}

		$consumerKey = $this->getContainer()->getParameter('twitter_consumer_key');
		$consumerSecret = $this->getContainer()->getParameter('twitter_consumer_secret');
		$accessToken = $this->getContainer()->getParameter('twitter_access_token');
		$accessTokenSecret = $this->getContainer()->getParameter('twitter_access_secret');

		// Setup CodeBird
		\Codebird\Codebird::setConsumerKey($consumerKey, $consumerSecret);
		$cb = \Codebird\Codebird::getInstance();
		$cb->setToken($accessToken, $accessTokenSecret);

		if ($forced || $forcedTwitter) {

			// Upload media
			$mainPicture = $entity->getMainPicture();
			if (!is_null($mainPicture)) {
				if ($verbose) {
					$output->write('<info>Uploading ('.$mainPicture->getPath().') to Twitter...</info>');
				}
				// upload all media files
				$reply = $cb->media_upload(array(
					'media' => $mainPicture->getAbsolutePath(),
				));
				if (isset($reply->httpstatus) && $reply->httpstatus == 200 && isset($reply->media_id_string)) {
					// and collect their IDs
					$mediaIds = $reply->media_id_string;
					if ($verbose) {
						$output->writeln('<fg=cyan>[Done] (media_id_string='.$reply->media_id_string.')</fg=cyan>');
					}
				} else {
					if ($verbose) {
						$output->writeln('<fg=cyan>[Error] ('.(isset($reply->httpstatus) ? $reply->httpstatus : 'unknow').') '.((isset($reply->errors) && isset($reply->errors[0]) && isset($reply->errors[0]->message)) ? $reply->errors[0]->message : '').'</fg=cyan>');
					}
				}
			}
			$params = array(
				'status'    => $status,
				'media_ids' => $mediaIds,
			);

			$reply = $cb->statuses_update($params);
			$success = isset($reply->httpstatus) && $reply->httpstatus == 200;
			if ($verbose) {
				if ($success) {
					$output->writeln('<fg=cyan>[Done]</fg=cyan>');
				} else {
					$output->writeln('<fg=red>[Error] ('.(isset($reply->httpstatus) ? $reply->httpstatus : 'unknow').') '.((isset($reply->errors) && isset($reply->errors[0]) && isset($reply->errors[0]->message)) ? $reply->errors[0]->message : '').'</fg=red>');
				}
			}

		} else {
			if ($verbose) {
				$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
			}
		}

		return $success;
	}

	private function _publishOnFacebook($spotlight, $entity, $forced, $forcedFacebook, $verbose, $output) {

		$success = false;
		$message = $this->getContainer()->get('templating')->render('LadbCoreBundle:Command:_cron-spotlight-facebook-message.txt.twig', array( 'spotlight' => $spotlight, 'entity' => $entity));
		$link = $this->getContainer()->get('templating')->render('LadbCoreBundle:Command:_cron-spotlight-facebook-link.txt.twig', array( 'spotlight' => $spotlight, 'entity' => $entity));
		if ($verbose) {
			$output->writeln('<info>Posting to Facebook (<fg=yellow>'.$message.' '.$link.'</fg=yellow>) ...</info>');
		}

		$appId = $this->getContainer()->getParameter('facebook_app_id');
		$appSecret = $this->getContainer()->getParameter('facebook_app_secret');
		$pageId = $this->getContainer()->getParameter('facebook_page_id');
		$accessToken = $this->getContainer()->getParameter('facebook_access_token');

		// Setup Facebook SDK
		FacebookSession::setDefaultApplication($appId, $appSecret);
		$session = new FacebookSession($accessToken);

		if ($forced || $forcedFacebook) {

			try {

				$reply = (new FacebookRequest($session, 'POST', '/'. $pageId .'/feed', array(
					'access_token' => $accessToken,
					'message' => $message,
					'link' => $link,
				) ))->execute()->getGraphObject()->asArray();

				$success = isset($reply['id']);
				if ($verbose) {
					if ($success) {
						$output->writeln('<fg=cyan>[Done]</fg=cyan>');
					} else {
						$output->writeln('<fg=red>[Error]</fg=red>');
					}
				}

			} catch (\Exception $e) {
				$success = false;
				if ($verbose) {
					$output->writeln('<fg=red>[Error] ('.$e->getMessage().')</fg=red>');
				}
			}

		} else {
			if ($verbose) {
				$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
			}
		}

		return $success;
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$forcedTwitter = $input->getOption('force-twitter');
		$forcedFacebook = $input->getOption('force-facebook');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$spotlightRepository = $om->getRepository(Spotlight::CLASS_NAME);
		$typableUtils = $this->getContainer()->get(TypableUtils::NAME);

		// Retrieve current spotlight

		$currentSpotlight = $spotlightRepository->findOneLast();
		if (!is_null($currentSpotlight)) {

			$currentSpotlightAge = $currentSpotlight->getCreatedAt()->diff(new \DateTime());
			if ($currentSpotlightAge->d > 0) {
				$currentSpotlight->setFinishedAt(new \DateTime());
				if ($forced) {
					$om->flush();
				}
				if ($verbose) {
					$output->writeln('<info>Current spotlight finished</info>');
				}
				$currentSpotlight = null;
			} else {
				if ($verbose) {
					$output->writeln('<info>Current spotlight still active</info>');
				}

				$entity = $typableUtils->findTypable($currentSpotlight->getEntityType(), $currentSpotlight->getEntityId());
				if ($forcedTwitter) {
					$this->_publishOnTwitter($currentSpotlight, $entity, $forced, $forcedTwitter, $verbose, $output);
				}
				if ($forcedFacebook) {
					$this->_publishOnFacebook($currentSpotlight, $entity, $forced, $forcedFacebook, $verbose, $output);
				}
			}

		}

		if (is_null($currentSpotlight)) {

			$retrieveDate = (new \DateTime())->sub(new \DateInterval('P7D'));	// 7 days

			// Retrieve new highscored creation

			if ($verbose) {
				$output->writeln('<info>Checking Creations...</info>');
			}

			$queryBuilder = $om->createQueryBuilder();
			$queryBuilder
				->select(array( 'c', 's', '(c.likeCount * 100 + c.commentCount * 2 + c.viewCount + c.planCount * 10 + c.howtoCount * 10) as score' ))
				->from('LadbCoreBundle:Wonder\Creation', 'c')
				->leftJoin('c.spotlight', 's')
				->where('c.isDraft = false')
				->andWhere('c.likeCount > 2')		// 3 or more likes
				->andWhere('c.changedAt > :retrieveDate')
				->andWhere('c.spotlight IS NULL')
				->orderBy('score', 'DESC')
				->setParameter('retrieveDate', $retrieveDate)
				->setMaxResults(1)
			;

			try {
				$creationResult = $queryBuilder->getQuery()->getSingleResult();
			} catch (\Doctrine\ORM\NoResultException $e) {
				$creationResult = null;
			}

			// Retrieve new highscored howto

			if ($verbose) {
				$output->writeln('<info>Checking Howtos...</info>');
			}

			$queryBuilder = $om->createQueryBuilder();
			$queryBuilder
				->select(array( 'h', 's', '(h.likeCount * 100 + h.commentCount * 2 + h.viewCount + h.creationCount * 10 + h.planCount * 10 + h.workshopCount * 10) as score' ))
				->from('LadbCoreBundle:Howto\Howto', 'h')
				->leftJoin('h.spotlight', 's')
				->where('h.isDraft = false')
				->andWhere('h.likeCount > 2')		// 3 or more likes
				->andWhere('h.changedAt > :retrieveDate')
				->andWhere('h.spotlight IS NULL')
				->andWhere('h.isWorkInProgress = 0')
				->orderBy('score', 'DESC')
				->setParameter('retrieveDate', $retrieveDate)
				->setMaxResults(1)
			;

			try {
				$howtoResult = $queryBuilder->getQuery()->getSingleResult();
			} catch (\Doctrine\ORM\NoResultException $e) {
				$howtoResult = null;
			}

			$entity = null;
			$score = 0;
			if (!is_null($creationResult)) {
				$creation = $creationResult[0];
				$isValid = true;
				if ($creation->getHowtoCount() > 0) {
					foreach ($creation->getHowtos() as $howto) {
						if (!is_null($howto->getSpotlight()) && $howto->getSpotlight()->getCreatedAt() > $retrieveDate) {

							// Create a disabled spotlight
							$spotlight = new Spotlight();
							$spotlight->setEntityType($creation->getType());
							$spotlight->setEntityId($creation->getId());
							$spotlight->setEnabled(false);

							$creation->setSpotlight($spotlight);

							if ($forced) {
								$om->persist($spotlight);
								$om->flush();
							}
							if ($verbose) {
								$output->writeln('<info>Invalidate Creation associated with previous Howto spotlight : <fg=cyan>"'.$creation->getTitle().'" (type='.$creation->getType().')</fg=cyan></info>');
							}

							$isValid = false;
							break;
						}
					}
				}
				if ($isValid) {
					$entity = $creation;
					$score = $creationResult['score'];
				}
			}
			if (!is_null($howtoResult)) {
				$howto = $howtoResult[0];
				$isValid = true;
				if ($howto->getCreationCount() > 0) {
					foreach ($howto->getCreations() as $creation) {
						if (!is_null($creation->getSpotlight()) && $creation->getSpotlight()->getCreatedAt() > $retrieveDate) {

							// Create a disabled spotlight
							$spotlight = new Spotlight();
							$spotlight->setEntityType($howto->getType());
							$spotlight->setEntityId($howto->getId());
							$spotlight->setEnabled(false);

							$howto->setSpotlight($spotlight);

							if ($forced) {
								$om->persist($spotlight);
								$om->flush();
							}
							if ($verbose) {
								$output->writeln('<info>Invalidate Howto associated with previous Creation spotlight : <fg=cyan>"'.$creation->getTitle().'" (type='.$creation->getType().')</fg=cyan></info>');
							}

							$isValid = false;
							break;
						}
					}
				}
				if ($isValid && $howtoResult['score'] > $score) {
					$entity = $howto;
				}
			}

			if (!is_null($entity)) {

				$spotlight = new Spotlight();
				$spotlight->setEntityType($entity->getType());
				$spotlight->setEntityId($entity->getId());

				$entity->setSpotlight($spotlight);

				if ($forced) {
					$om->persist($spotlight);
					$om->flush();
				}
				if ($verbose) {
					$output->writeln('<info>New spotlight : <fg=cyan>"'.$entity->getTitle().'" (type='.$entity->getType().')</fg=cyan>'.($forced ? ' created' : ' to create').'</info>');
				}

				// Publish on social networks
				$twitterSuccess = $this->_publishOnTwitter($spotlight, $entity, $forced, $forcedTwitter, $verbose, $output);
				$facebookSuccess = $this->_publishOnFacebook($spotlight, $entity, $forced, $forcedFacebook, $verbose, $output);

				// Email notification
				$mailerUtils = $this->getContainer()->get(MailerUtils::NAME);
				if ($verbose) {
					$output->write('<info>Sending notification to '.$entity->getUser()->getDisplayname().'...</info>');
				}
				if ($forced) {
					$mailerUtils->sendNewSpotlightNotificationEmailMessage($entity->getUser(), $spotlight, $entity, $twitterSuccess, $facebookSuccess);
					if ($verbose) {
						$output->writeln('<fg=cyan>[Done]</fg=cyan>');
					}
				} else {
					if ($verbose) {
						$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
					}
				}

			} else {
				if ($verbose) {
					$output->writeln('<info>No new spotlight</info>');
				}
			}

		}

	}

}