<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:clear')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Clear database')
			->setHelp(<<<EOT
The <info>ladb:clear</info> command drop the default connections
database en create a new one :

<info>php app/console ladb:drop</info>

<error>Be careful: All data in a given database will be lost when executing
this command.</error>
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		// Run doctrine:database:drop --force

		$forced = $input->getOption('force');

		$command = $this->getApplication()->find('doctrine:database:drop');
		$arguments = array(
			'command' => 'doctrine:database:drop',
			'--force' => $forced,
		);

		$dropInput = new ArrayInput($arguments);
		$returnCode = $command->run($dropInput, $output);

		if ($returnCode) {
			return $returnCode;
		}

		// Run doctrine:database:create

		$command = $this->getApplication()->find('doctrine:database:create');
		$arguments = array(
			'command' => 'doctrine:database:create',
		);

		$createInput = new ArrayInput($arguments);
		$returnCode = $command->run($createInput, $output);

		if ($returnCode) {
			return $returnCode;
		}

		$output->writeln('<info>Ladb clear complete</info>');
	}

}