<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateHeightRatio100Command extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:heightratio100')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate pictures heightratio100')
			->setHelp(<<<EOT
The <info>ladb:generate:heightratio100</info> command generate pictures heightratio100
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Count pictures /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'count(p.id)' ))
			->from('LadbCoreBundle:Core\Picture', 'p')
		;

		try {
			$pictureCount = $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$pictureCount = 0;
		}

		$output->writeln('<comment> ['.$pictureCount.' pictures]</comment>');

		$progress = new ProgressBar($output, $pictureCount);
		$progress->start();

		$batchSize = 1000;
		$batchCount = $pictureCount / $batchSize;

		for ($batchIndex = 0; $batchIndex <= $batchCount; $batchIndex++) {

			// Extract pictures /////

			$queryBuilder = $om->createQueryBuilder();
			$queryBuilder
				->select(array( 'p' ))
				->from('LadbCoreBundle:Core\Picture', 'p')
				->setFirstResult($batchIndex * $batchSize)
				->setMaxResults($batchSize)
			;

			try {
				$pictures = $queryBuilder->getQuery()->getResult();
			} catch (\Doctrine\ORM\NoResultException $e) {
				$pictures = array();
			}

			foreach ($pictures as $picture) {
				$progress->advance();
				try {
					if ($verbose) {
						$output->write('<info>Processing Picture ID='.$picture->getId().'...</info>');
					}
					list($width, $height, $type, $attr) = getimagesize($picture->getAbsolutePath());
					$heightRatio100 = $width > 0 ? $height / $width * 100 : 1;
					$picture->setWidth($width);
					$picture->setHeight($height);
					$picture->setHeightRatio100($heightRatio100);
					if ($verbose) {
						$output->writeln('<info>heightRatio100='.$heightRatio100.'</info><comment> [Done]</comment>');
					}
				} catch(\Exception $e) {
					$output->writeln('<error>Error loading Picture ID='.$picture->getId().'</error>');
				}
			}

			if ($forced) {
				$om->flush();
			}

		}

		$progress->finish();

		$output->writeln('<comment>[Finished]</comment>');

	}

}