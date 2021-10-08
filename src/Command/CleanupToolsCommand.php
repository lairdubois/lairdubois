<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupToolsCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:tools')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup tools')
			->setHelp(<<<EOT
The <info>ladb:cleanup:tools</info> command remove unused tools
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Extract tools /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f' ))
			->from('App\Entity\Input\Tool', 'f')
		;

		try {
			$tools = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		$toolCounters = array();
		foreach ($tools as $tool) {
			$toolCounters[$tool->getId()] = array( $tool, 0 );
		}

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'f' ))
			->from('App\Entity\Wonder\Creation', 'c')
			->leftJoin('c.tools', 'f')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($creations as $creation) {
			foreach ($creation->getTools() as $tool) {
				$toolCounters[$tool->getId()][1]++;
			}
		}

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedToolCount = 0;
		foreach ($toolCounters as $toolCounter) {
			$counter = $toolCounter[1];
			if ($counter == 0) {
				$unusedToolCount++;
				$tool = $toolCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$tool->getName().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($tool);
				}
			}
		}

		if ($forced) {
			if ($unusedToolCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedToolCount.' tools removed</info>');
		} else {
			$output->writeln('<info>'.$unusedToolCount.' tools to remove</info>');
		}

        return Command::SUCCESS;
	}

}