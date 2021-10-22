<?php

namespace App\Command;

use App\Utils\TypableUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CronNotificationCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:cron:notification')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Execute notifications commands')
			->setHelp(<<<EOT
The <info>ladb:cron:notification:email</info> Execute notifications commands
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		// Populate command

		if ($verbose) {
			$output->writeln('<info>Executing ladb:cron:notification:populate command...</info>');
		}

		$populateCommand = $this->getApplication()->find('ladb:cron:notification:populate');
		$arguments = array(
			'--force' => $forced,
			'--verbose' => $verbose,
		);
		$input = new ArrayInput($arguments);
		$returnCode = $populateCommand->run($input, $output);
		if ($returnCode != 0) {
			if ($verbose) {
				$output->writeln('<error>Error in ladb:cron:notification:populate command</error>');
			}
			return Command::FAILURE;
		}

		// Email command

		if ($verbose) {
			$output->writeln('<info>Executing ladb:cron:notification:email command...</info>');
		}

		$emailCommand = $this->getApplication()->find('ladb:cron:notification:email');
		$arguments = array(
			'--force' => $forced,
			'--verbose' => $verbose,
		);
		$input = new ArrayInput($arguments);
		$returnCode = $emailCommand->run($input, $output);
		if ($returnCode != 0) {
			if ($verbose) {
				$output->writeln('<error>Error in ladb:cron:notification:email command</error>');
			}
			return Command::FAILURE;
		}

        return Command::SUCCESS;

	}

}