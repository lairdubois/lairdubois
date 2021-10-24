<?php

namespace App\Command;

use App\Utils\FieldPreprocessorUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\Block\Text;

class MigrateFindsCommand extends AbstractContainerAwareCommand {

	private $toTransferCommentables = array();
	private $toTransferVotables = array();

	protected function configure() {
		$this
			->setName('ladb:migrate:finds')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate find body to body blocks')
			->setHelp(<<<EOT
The <info>ladb:migrate:providers</info> command migrate find body to body blocks
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getDoctrine()->getManager();
		$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);

		// Retrieve Finds

		$output->write('<info>Retrieve finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f' ))
			->from('App\Entity\Find\Find', 'f')
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$finds = array();
		}

		$output->writeln('<comment> ['.count($finds).' finds]</comment>');

		foreach ($finds as $find) {

			$output->writeln('<info>Processing <fg=cyan>'.$find->getTitle().'</fg=cyan> ...</info>');

			$body = $find->getBody();

			$block = new Text();
			$block->setBody($body);
			$fieldPreprocessorUtils->preprocessFields($block);

			$find->resetBodyBlocks();
			$find->addBodyBlock($block);


			$output->writeln('<comment>[Done]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}