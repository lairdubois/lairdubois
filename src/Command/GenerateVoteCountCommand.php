<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\Vote;

class GenerateVoteCountCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:votecount')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate votecount')
			->setHelp(<<<EOT
The <info>ladb:generate:votecount</info> command generate textures
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		// Retrieve Woods

		$output->write('<info>Resetting values...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'v' ))
			->from('App\Entity\Knowledge\Value\BaseValue', 'v')
		;

		try {
			$values = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$values = array();
		}

		$output->writeln('<comment> ['.count($values).' $values]</comment>');

		foreach ($values as $value) {

			$output->write('<info>Processing '.get_class($value).' ...</info>');

			$votes = $voteRepository->findByEntityTypeAndEntityId($value->getType(), $value->getId());
			$value->setVoteCount(count($votes));

			$output->writeln('<comment> ['.count($votes).' votes]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}