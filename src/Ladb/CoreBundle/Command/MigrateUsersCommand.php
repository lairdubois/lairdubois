<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUsersCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:users')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate users')
			->setHelp(<<<EOT
The <info>ladb:migrate:users</info> command migrate users
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$verbose = $input->getOption('verbose');
		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Count users /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array('count(u.id)'))
			->from('LadbCoreBundle:Core\User', 'u');

		try {
			$userCount = $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$userCount = 0;
		}

		$output->writeln('<comment> ['.$userCount.' users]</comment>');

		$progress = new ProgressBar($output, $userCount);
		$progress->start();

		$batchSize = 1000;
		$batchCount = $userCount / $batchSize;

		for ($batchIndex = 0; $batchIndex <= $batchCount; $batchIndex++) {

			// Extract users /////

			$queryBuilder = $om->createQueryBuilder();
			$queryBuilder
				->select(array('u'))
				->from('LadbCoreBundle:Core\User', 'u')
				->setFirstResult($batchIndex * $batchSize)
				->setMaxResults($batchSize);

			try {
				$users = $queryBuilder->getQuery()->getResult();
			} catch (\Doctrine\ORM\NoResultException $e) {
				$users = array();
			}

			foreach ($users as $user) {
				$progress->advance();
				if ($verbose) {
					$output->write('<info>Processing User username='.$user->getUsername().'...</info>');
				}

				$meta = $user->getMeta();
				$meta->setBanner($user->getDeprecatedBanner());
				$meta->setBiography($user->getDeprecatedBiography());
				foreach ($user->getDeprecatedSkills() as $skill) {
					$meta->addSkill($skill);
				}

				$meta->setWebsite($user->getDeprecatedWebsite());
				$meta->setFacebook($user->getDeprecatedFacebook());
				$meta->setTwitter($user->getDeprecatedTwitter());
				$meta->setGoogleplus($user->getDeprecatedGoogleplus());
				$meta->setYoutube($user->getDeprecatedYoutube());
				$meta->setVimeo($user->getDeprecatedVimeo());
				$meta->setPinterest($user->getDeprecatedPinterest());
				$meta->setInstagram($user->getDeprecatedInstagram());

				$meta->setAutoWatchEnabled($user->getDeprecatedAutoWatchEnabled());
				$meta->setIncomingMessageEmailNotificationEnabled($user->getDeprecatedIncomingMessageEmailNotificationEnabled());
				$meta->setNewFollowerEmailNotificationEnabled($user->getDeprecatedNewFollowerEmailNotificationEnabled());
				$meta->setNewLikeEmailNotificationEnabled($user->getDeprecatedNewLikeEmailNotificationEnabled());
				$meta->setNewVoteEmailNotificationEnabled($user->getDeprecatedNewVoteEmailNotificationEnabled());
				$meta->setNewFollowingPostEmailNotificationEnabled($user->getDeprecatedNewFollowingPostEmailNotificationEnabled());
				$meta->setNewWatchActivityEmailNotificationEnabled($user->getDeprecatedNewWatchActivityEmailNotificationEnabled());
				$meta->setNewSpotlightEmailNotificationEnabled($user->getDeprecatedNewSpotlightEmailNotificationEnabled());
				$meta->setWeekNewsEmailEnabled($user->getDeprecatedWeekNewsEmailEnabled());

				$meta->incrementFollowerCount($user->getDeprecatedFollowerCount());
				$meta->incrementFollowingCount($user->getDeprecatedFollowingCount());
				$meta->incrementRecievedLikeCount($user->getDeprecatedRecievedLikeCount());
				$meta->incrementSentLikeCount($user->getDeprecatedSentLikeCount());
				$meta->incrementPositiveVoteCount($user->getDeprecatedPositiveVoteCount());
				$meta->incrementNegativeVoteCount($user->getDeprecatedNegativeVoteCount());
				$meta->incrementUnreadMessageCount($user->getDeprecatedUnreadMessageCount());
				$meta->incrementFreshNotificationCount($user->getDeprecatedFreshNotificationCount());
				$meta->incrementCommentCount($user->getDeprecatedCommentCount());

				$meta->incrementContributionCount($user->getDeprecatedContributionCount());

				$meta->incrementPrivateCreationCount($user->getDeprecatedDraftCreationCount());
				$meta->incrementPublicCreationCount($user->getDeprecatedPublishedCreationCount());
				$meta->incrementPrivatePlanCount($user->getDeprecatedDraftPlanCount());
				$meta->incrementPublicPlanCount($user->getDeprecatedPublishedPlanCount());
				$meta->incrementPrivateHowtoCount($user->getDeprecatedDraftHowtoCount());
				$meta->incrementPublicHowtoCount($user->getDeprecatedPublishedHowtoCount());
				$meta->incrementPrivateWorkshopCount($user->getDeprecatedDraftWorkshopCount());
				$meta->incrementPublicWorkshopCount($user->getDeprecatedPublishedWorkshopCount());
				$meta->incrementPrivateFindCount($user->getDeprecatedDraftFindCount());
				$meta->incrementPublicFindCount($user->getDeprecatedPublishedFindCount());
				$meta->incrementPrivateQuestionCount($user->getDeprecatedDraftQuestionCount());
				$meta->incrementPublicQuestionCount($user->getDeprecatedPublishedQuestionCount());
				$meta->incrementAnswerCount($user->getDeprecatedAnswerCount());
				$meta->incrementPrivateGraphicCount($user->getDeprecatedDraftGraphicCount());
				$meta->incrementPublicGraphicCount($user->getDeprecatedPublishedGraphicCount());

				$meta->incrementProposalCount($user->getDeprecatedProposalCount());
				$meta->incrementTestimonialCount($user->getDeprecatedTestimonialCount());

				if ($verbose) {
					$output->writeln('<comment> [Done]</comment>');
				}
			}

			if ($forced) {
				$om->flush();
			}

			unset($users);

		}

		$progress->finish();

		$output->writeln('<comment>[Finished]</comment>');

	}

}