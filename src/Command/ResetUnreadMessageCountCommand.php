<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Message\MessageMeta;

class ResetUnreadMessageCountCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:reset:unreadmessagecount')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Reset unreadMessageCount')
			->setHelp(<<<EOT
The <info>ladb:reset:unreadmessagecount</info> command reset unreadMessageCount
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$verbose = $input->getOption('verbose');
		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$messageMetaRepository = $om->getRepository(MessageMeta::CLASS_NAME);

		// Count users /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array('count(u.id)'))
			->from('App\Entity\Core\User', 'u');

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
				->from('App\Entity\Core\User', 'u')
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

				$unreadMessageCount = 0;

				$messageMetas = $messageMetaRepository->findByParticipant($user);
				foreach ($messageMetas as $messageMeta) {
					if (!$messageMeta->getIsRead()) {
						$unreadMessageCount++;
					}
				}

				// Set unread message count
				$user->getMeta()->setUnreadMessageCount($unreadMessageCount);

				if ($verbose) {
					$output->writeln('<fg=cyan> ['.$unreadMessageCount.' messages]</fg=cyan>');
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