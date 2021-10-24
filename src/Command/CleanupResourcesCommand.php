<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupResourcesCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:resources')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup resources')
			->setHelp(<<<EOT
The <info>ladb:cleanup:resources</info> command remove unused resources
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getDoctrine()->getManager();

		// Extract resources /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from('App\Entity\Core\Resource', 'r')
		;

		try {
			$resources = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}

		$resourceCounters = array();
		foreach ($resources as $resource) {
			$resourceCounters[$resource->getId()] = array( $resource, 0 );
		}

		// Check plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 'r' ))
			->from('App\Entity\Wonder\Plan', 'p')
			->leftJoin('p.resources', 'r')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$plans = array();
		}

		foreach ($plans as $plan) {
			foreach ($plan->getResources() as $resource) {
				$resourceCounters[$resource->getId()][1]++;
			}
		}
		unset($plans);

		// Check Graphics /////

		$output->writeln('<info>Checking graphics...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'g', 'r' ))
			->from('App\Entity\Promotion\Graphic', 'g')
			->leftJoin('g.resource', 'r')
		;

		try {
			$graphics = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$graphics = array();
		}

		foreach ($graphics as $graphic) {
			$resource = $graphic->getResource();
			if (!is_null($resource)) {
				$resourceCounters[$resource->getId()][1]++;
			}
		}
		unset($graphics);

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedResourceCount = 0;
		foreach ($resourceCounters as $resourceCounter) {
			$counter = $resourceCounter[1];
			if ($counter == 0) {
				$unusedResourceCount++;
				$resource = $resourceCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$resource->getPath().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($resource);
				}
			}
		}

		if ($forced) {
			if ($unusedResourceCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedResourceCount.' resources removed</info>');
		} else {
			$output->writeln('<info>'.$unusedResourceCount.' resources to remove</info>');
		}

        return Command::SUCCESS;
	}

}