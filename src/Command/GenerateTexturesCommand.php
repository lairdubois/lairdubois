<?php

namespace App\Command;

use App\Utils\TextureUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use App\Model\AuthoredInterface;
use App\Utils\TypableUtils;

class GenerateTexturesCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:textures')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate textures')
			->setHelp(<<<EOT
The <info>ladb:generate:textures</info> command generate textures
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getDoctrine()->getManager();
		$textureUtils = $this->get(TextureUtils::class);

		// Retrieve Woods

		$output->write('<info>Resetting woods...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from('App\Entity\Knowledge\Wood', 'w')
		;

		try {
			$woods = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$woods = array();
		}

		$output->writeln('<comment> ['.count($woods).' woods]</comment>');

		foreach ($woods as $wood) {

			$output->write('<info>Processing '.$wood->getName().' ...</info>');

			// Remove wood textures
			foreach($wood->getTextures() as $texture) {
				$om->remove($texture);
			}
			$wood->setTextureCount(0);

			// Create grain textures
			foreach ($wood->getGrainValues() as $grainValue) {
				try {
					$textureUtils->createTexture($wood, $grainValue, false);
				} catch (\Exception $e) {
					$output->writeln('<error>'.$e->getMessage().'</error>');
				}
			}

			// Create endgrain textures
			foreach ($wood->getEndgrainValues() as $endgrainValue) {
				try {
					$textureUtils->createTexture($wood, $endgrainValue, false);
				} catch (\Exception $e) {
					$output->writeln('<error>'.$e->getMessage().'</error>');
				}
			}

			$output->writeln('<comment> ['.$wood->getTextureCount().' textures]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}