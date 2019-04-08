<?php

namespace Ladb\CoreBundle\Command;

use DirkGroenen\Pinterest\Pinterest;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Model\StripableInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Facebook\FacebookSession;
use Ladb\CoreBundle\Entity\Core\Spotlight;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\TypableUtils;

class CronSpotlightCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:spotlight')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->addOption('force-twitter', null, InputOption::VALUE_NONE, 'Force posting on Twitter')
			->addOption('force-facebook', null, InputOption::VALUE_NONE, 'Force posting on Facebook')
			->addOption('force-pinterest', null, InputOption::VALUE_NONE, 'Force posting on Pinterest')
			->addOption('force-mastodon', null, InputOption::VALUE_NONE, 'Force posting on Mastodon')
			->setDescription('Update spotlights')
			->setHelp(<<<EOT
The <info>ladb:cron:spotlight</info> update spotlights
EOT
			);
	}

	/////

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$forcedTwitter = $input->getOption('force-twitter');
		$forcedFacebook = $input->getOption('force-facebook');
		$forcedPinterest = $input->getOption('force-pinterest');
		$forcedMastodon = $input->getOption('force-mastodon');
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
				if ($forcedPinterest) {
					$this->_publishOnPinterest($currentSpotlight, $entity, $forced, $forcedPinterest, $verbose, $output);
				}
				if ($forcedMastodon) {
					$this->_publishOnMastodon($currentSpotlight, $entity, $forced, $forcedMastodon, $verbose, $output);
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
				->andWhere('c.likeCount > 4')		// 5 or more likes
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
				$pinterestSuccess = false; // 2018-04-24 LADB acces_token was banned by Pinterest // $this->_publishOnPinterest($spotlight, $entity, $forced, $forcedPinterest, $verbose, $output);
				$mastodonSuccess = $this->_publishOnMastodon($spotlight, $entity, $forced, $forcedMastodon, $verbose, $output);

				// Email notification
				$mailerUtils = $this->getContainer()->get(MailerUtils::NAME);
				if ($verbose) {
					$output->write('<info>Sending notification to '.$entity->getUser()->getDisplayname().'...</info>');
				}
				if ($forced) {
					$mailerUtils->sendNewSpotlightNotificationEmailMessage($entity->getUser(), $spotlight, $entity, $twitterSuccess, $facebookSuccess, $pinterestSuccess, $mastodonSuccess);
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

		$success = true;
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
		$fb = new \Facebook\Facebook([
			'app_id' => $appId,
			'app_secret' => $appSecret,
			'default_graph_version' => 'v2.8',
			'default_access_token' => $accessToken,
		]);

		if ($forced || $forcedFacebook) {

			$request = $fb->request(
				'POST',
				'/'. $pageId .'/feed',
				array(
					'message' => $message,
					'link' => $link,
				)
			);

			try {
				$fb->getClient()->sendRequest($request);
			} catch (\Facebook\Exceptions\FacebookResponseException $e) {

				// When Graph returns an error
				if ($verbose) {
					$output->writeln('<fg=red>[Error] Graph returned an error: '.$e->getMessage().'</fg=red>');
				}

				$success = false;
			} catch (\Facebook\Exceptions\FacebookSDKException $e) {

				// When validation fails or other local issues
				if ($verbose) {
					$output->writeln('<fg=red>[Error] Facebook SDK returned an error: '.$e->getMessage().'</fg=red>');
				}

				$success = false;
			}

			if ($verbose && $success) {
				$output->writeln('<fg=cyan>[Done]</fg=cyan>');
			}

		} else {
			if ($verbose) {
				$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
			}
		}

		return $success;
	}

	private function _publishOnPinterest($spotlight, $entity, $forced, $forcedPinterest, $verbose, $output) {

		$success = false;

		if (($entity instanceof Creation || $entity instanceof Howto) && !is_null($entity->getMainPicture())) {

			$board = 'lairdubois/'.( $entity instanceof Creation ? 'les-créations' : ($entity instanceof Howto ? 'les-pas-à-pas' : '') );
			$note = $this->getContainer()->get('templating')->render('LadbCoreBundle:Command:_cron-spotlight-pinterest-note.txt.twig', array( 'spotlight' => $spotlight, 'entity' => $entity));
			if ($verbose) {
				$output->writeln('<info>Posting to Pinterest (<fg=yellow>on board='.$board.' note='.$note.'</fg=yellow>) ...</info>');
			}

			$clientId = $this->getContainer()->getParameter('pinterest_client_id');
			$clientSecret = $this->getContainer()->getParameter('pinterest_client_secret');
			$accessToken = $this->getContainer()->getParameter('pinterest_access_token');

			$pinterest = new Pinterest($clientId, $clientSecret);
			$pinterest->auth->setOAuthToken($accessToken);

			if ($forced || $forcedPinterest) {

				$typableUtils = $this->getContainer()->get(TypableUtils::NAME);

				$imageUrl = $entity instanceof StripableInterface ? $typableUtils->getUrlAction($entity, 'strip', true, false) : $this->getContainer()->get('liip_imagine.cache.manager')->getBrowserPath($entity->getMainPicture()->getAbsolutePath(), '600x600i');;
				$link = $typableUtils->getUrlAction($entity);

				try {

					$pin = $pinterest->pins->create(array(
						'board'     => $board,
						'image_url' => $imageUrl,
						'note'      => $note,
						'link'      => $link,
					));

					if ($pin) {
						$success = true;
					}

				} catch (\Exception $e) {

					// Pinterest error
					if ($verbose) {
						$output->writeln('<fg=red>[Error] Pinterest returned an error: '.$e->getMessage().'</fg=red>');
					}

					$success = false;
				}

				if ($verbose && $success) {
					$output->writeln('<fg=cyan>[Done]</fg=cyan>');
				}

			} else {
				if ($verbose) {
					$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
				}
			}

		} else {
			if ($verbose) {
				$output->writeln('<fg=red>[Error] Only Creation and Howto with not null main picture can be published on Pinterest.</fg=red>');
			}
		}

		return $success;
	}

	private function _publishOnMastodon($spotlight, $entity, $forced, $forcedMastodon, $verbose, $output) {

		$status = $this->getContainer()->get('templating')->render('LadbCoreBundle:Command:_cron-spotlight-mastodon-status.txt.twig', array( 'spotlight' => $spotlight, 'entity' => $entity));
		if ($verbose) {
			$output->writeln('<info>Posting to Mastodon (<fg=yellow>'.$status.'</fg=yellow>) ...</info>');
		}

		$mastodonInstance = $this->getContainer()->getParameter('mastodon_instance');
		$accessToken = $this->getContainer()->getParameter('mastodon_access_token');

		$headers = array(
			'Authorization: Bearer '.$accessToken,
		);

		$status_data = array(
			"status" => $status,
			"language" => "fra",
			"visibility" => "public"
		);

		$ch_status = curl_init();
		curl_setopt($ch_status, CURLOPT_URL, $mastodonInstance."/api/v1/statuses");
		curl_setopt($ch_status, CURLOPT_POST, 1);
		curl_setopt($ch_status, CURLOPT_POSTFIELDS, $status_data);
		curl_setopt($ch_status, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch_status, CURLOPT_HTTPHEADER, $headers);

		if ($forced || $forcedMastodon) {

			$output_status = json_decode(curl_exec($ch_status));

			if ($verbose && $output_status) {
				$output->writeln('<fg=cyan>[Done]</fg=cyan>');
			}
		} else {
			if ($verbose) {
				$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
			}
		}

		curl_close ($ch_status);

	}

}