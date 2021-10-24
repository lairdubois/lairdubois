<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\Vote;

class GenerateArticlesSortIndexCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:articlessortindex')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate articles sortindex')
			->setHelp(<<<EOT
The <info>ladb:generate:articlessortindex</info> command generate articles sortindex
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		// Retrieve Howtos

		$output->write('<info>Retrieve howtos ...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h' ))
			->from('App\Entity\Howto\Howto', 'h')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$howtos = array();
		}

		$output->writeln('<comment> ['.count($howtos).' $values]</comment>');

		foreach ($howtos as $howto) {

			$output->write('<info>Processing '.$howto->getTitle().' [id='.$howto->getId().'] ...</info>');

			$sortIndex = 0;
			foreach ($howto->getArticles() as $article) {
				$article->setSortIndex($sortIndex);
				$sortIndex++;
			}

			$output->writeln('<comment> [maxSortIndex='.($sortIndex - 1).']</comment>');

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}