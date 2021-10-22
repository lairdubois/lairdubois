<?php

namespace App\Command;

use App\Entity\Core\User;
use App\Fos\DisplaynameCanonicalizer;
use App\Fos\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateUsersCommand extends AbstractCommand {

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
		$userRepository = $om->getRepository(User::class);
		$userManager = $this->getContainer()->get(UserManager::class);
		$displaynameCanonicalizer = $this->getContainer()->get(DisplaynameCanonicalizer::class);

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
					$output->write('<info>Processing User username='.$user->getUsername().' id='.$user->getId().'...</info>');
				}

				try {

					$displaynameCanonical = $displaynameCanonicalizer->canonicalize($user->getDisplayname());

					// Check if displayname exists
					$existingUser = $userRepository->findOneByDisplaynameCanonical($displaynameCanonical);
					if (!is_null($existingUser) && $user != $existingUser) {
						$output->writeln('<error>Not unique displayname='.$user->getDisplayname().' id='.$user->getId().'</error>', 0);
						if ($existingUser->getUsernameCanonical() != $displaynameCanonical) {
							$existingUser->setDisplayName($existingUser->getUsername());
							$userManager->updateUser($existingUser, false);
						}
						if ($user->getUsernameCanonical() != $displaynameCanonical) {
							$user->setDisplayName($user->getUsername());
						}
					}

				} catch (\Exception $e) {
					$output->writeln('<error>Invalid displayname='.$user->getDisplayname().' id='.$user->getId().'</error>', 0);
					$user->setDisplayName($user->getUsername());
				}

				$userManager->updateUser($user, false);

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

        return Command::SUCCESS;

	}

}