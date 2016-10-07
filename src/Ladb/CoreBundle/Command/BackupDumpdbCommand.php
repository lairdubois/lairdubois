<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class BackupDumpdbCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:backup:dumpdb')
			->addOption('dump-dir', null, InputOption::VALUE_REQUIRED, 'Define the dump dir', 'backups')
			->addOption('compress', null, InputOption::VALUE_NONE, 'Define if dump file is compressed')
			->addOption('overwrite', null, InputOption::VALUE_NONE, 'Define previous dump file is overwrited')
			->setDescription('Dump the database')
			->setHelp(<<<EOT
The <info>ladb:backup:db</info> command dump the database
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$dumpDir = $input->getOption('dump-dir');
		$compress = $input->getOption('compress');
		$overwrite = $input->getOption('overwrite');

		$dbHost = $this->getContainer()->getParameter('database_host');
		$dbPort = $this->getContainer()->getParameter('database_port');
		$dbUser = $this->getContainer()->getParameter('database_user');
		$dbPassword = $this->getContainer()->getParameter('database_password');
		$dbName = $this->getContainer()->getParameter('database_name');
		$sqlFile = $dbName.'.sql';
		$fs = new Filesystem();

		// Compute the mysqldump command

		$mysqldumpCommand = 'mysqldump';

		if (!is_null($dbUser)) {
			$mysqldumpCommand .= ' --user='.$dbUser;
		}
		if (!is_null($dbPassword)) {
			$mysqldumpCommand .= ' --password='.$dbPassword;
		}
		if (!is_null($dbHost)) {
			$mysqldumpCommand .= ' --host='.$dbHost;
		}
		if (!is_null($dbPort)) {
			$mysqldumpCommand .= ' --port='.$dbPort;
		}
		if (!is_null($dbName)) {
			$mysqldumpCommand .= ' '.$dbName;
		}
		if (!empty($dumpDir)) {

			$sqlFile = $dumpDir.'/'.$sqlFile;

			// Create dumpDir if it does not exist
			if (!$fs->exists($dumpDir)) {
				$fs->mkdir($dumpDir);
			}

		}

		// Remove previous dump
		if ($fs->exists($sqlFile)) {
			if ($overwrite) {
				$fs->remove($sqlFile);
			} else {
				$output->writeln('<error>A previous dump already exists. Use --overwrite to overwrite it.</error>');
				return;
			}
		}

		// Execute mysqldump command
		$mysqldumpCommand .= ' > '.$sqlFile;
		if (system($mysqldumpCommand) === false) {
			$output->writeln('<error>Error executing mysqldump command.</error>');
			return;
		}

		if ($compress && $fs->exists($sqlFile)) {

			$tgzFile = $sqlFile.'.tgz';

			if ($fs->exists($tgzFile)) {
				$fs->remove($tgzFile);
			}

			// Execute tar command
			$tarCommand = 'tar -czf '.$tgzFile.' '.$sqlFile;
			if (system($tarCommand) === false) {
				$output->writeln('<error>Error executing tar command.</error>');
				return;
			}

		}

		$output->writeln('<info>Dump complete at '.$sqlFile.'</info>');

	}

}