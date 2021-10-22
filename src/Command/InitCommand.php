<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:init')
			->setDescription('Reset database')
			->setHelp(<<<EOT
The <info>ladb:init</info> command create the default connections
database and update schema :

<info>php app/console ladb:init</info>

<error>Be careful: All data in a given database will be lost when executing
this command.</error>
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		// Run doctrine:schema:update --force

		$command = $this->getApplication()->find('doctrine:schema:update');
		$arguments = array(
			'command' => 'doctrine:schema:update',
			'--force' => true
		);

		$updateInput = new ArrayInput($arguments);
		$returnCode = $command->run($updateInput, $output);

		if ($returnCode) {
			return $returnCode;
		}

		$output->writeln('<info>Ladb init complete</info>');

        return Command::SUCCESS;
	}

}