<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateSpotlightsCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:spotlights')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate spotlights')
			->setHelp(<<<EOT
The <info>ladb:migrate:spotlights</info> command migrate spotlights
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$em = $this->getDoctrine()->getManager();

		// Retrieve resources

		$output->write('<info>Retrieve spotlights...</info>');

		$queryBuilder = $em->createQueryBuilder();
		$queryBuilder
			->select(array( 's' ))
			->from('App\Entity\Spotlight', 's')
		;

		try {
			$spotlights = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$spotlights = array();
		}

		$output->writeln('<comment> ['.count($spotlights).' spotlights]</comment>');

		foreach ($spotlights as $spotlight) {
			$creation = $spotlight->getCreation();
			if (!is_null($creation)) {

				$creation->setSpotlight($spotlight);

				$spotlight->setEntityType($creation->getType());
				$spotlight->setEntityId($creation->getId());

			}
		}

		if ($forced) {
			$em->flush();
		}

        return Command::SUCCESS;

	}

}