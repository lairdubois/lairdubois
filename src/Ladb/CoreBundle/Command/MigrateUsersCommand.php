<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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

		$forced = $input->getOption('force');

		$em = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve users

		$output->write('<info>Retrieve users...</info>');

		$queryBuilder = $em->createQueryBuilder();
		$queryBuilder
			->select(array( 'u' ))
			->from('LadbCoreBundle:User', 'u')
		;

		try {
			$users = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$users = array();
		}

		$output->writeln('<comment> ['.count($users).' users]</comment>');

		foreach ($users as $user) {
			$user->setNewSpotlightEmailNotificationEnabled(true);
		}

		if ($forced) {
			$em->flush();
		}

	}

}