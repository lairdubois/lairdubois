<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupWoodsCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:woods')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup woods')
			->setHelp(<<<EOT
The <info>ladb:cleanup:woods</info> command remove unused woods
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Extract woods /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from('LadbCoreBundle:Input\Wood', 'w')
		;

		try {
			$woods = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

		$woodCounters = array();
		foreach ($woods as $wood) {
			$woodCounters[$wood->getId()] = array( $wood, 0 );
		}

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'w' ))
			->from('LadbCoreBundle:Wonder\Creation', 'c')
			->leftJoin('c.woods', 'w')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

		foreach ($creations as $creation) {
			foreach ($creation->getWoods() as $wood) {
				$woodCounters[$wood->getId()][1]++;
			}
		}

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedWoodCount = 0;
		foreach ($woodCounters as $woodCounter) {
			$counter = $woodCounter[1];
			if ($counter == 0) {
				$unusedWoodCount++;
				$wood = $woodCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$wood->getName().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($wood);
				}
			}
		}

		if ($forced) {
			if ($unusedWoodCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedWoodCount.' woods removed</info>');
		} else {
			$output->writeln('<info>'.$unusedWoodCount.' woods to remove</info>');
		}
	}

}