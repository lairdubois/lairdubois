<?php

namespace App\Command;

use App\Entity\Core\Picture;
use App\Entity\Core\Resource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupUploadsCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:uploads')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup uploads')
			->setHelp(<<<EOT
The <info>ladb:cleanup:uploads</info> command remove unused uploaded files
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getDoctrine()->getManager();

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		// Extract pictures /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p' ))
			->from('App\Entity\Core\Picture', 'p')
		;

		try {
			$pictures = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$pictures = array();
		}

		// Extract resources /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from('App\Entity\Core\Resource', 'r')
		;

		try {
			$resources = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$resources = array();
		}

		// DefaultFiles /////

		$defaultFiles = array(
			'.',
			'..',
			'avatar.png',
			'empty.png',
			'empty_add.png',
		);

		// Retrieve files /////

		$uploadDir = __DIR__.'/../../../../uploads';
		$files = scandir($uploadDir);

		// Cleanup /////

		$unusedFiles = array();
		foreach ($files as $file) {

			if ($verbose) {
				$output->write('<info>Checking '.$file.' ...</info>');
			}

			$used = false;
			if (preg_match(Picture::ACCEPTED_FILE_TYPE, $file)) {
				foreach ($pictures as $picture) {
					if ($picture->getMasterPath() == $file) {
						$used = true;
						break;
					}
					if ($picture->getTransformedPath() == $file) {
						$used = true;
						break;
					}
				}
			} else if (preg_match(Resource::DEFAULT_ACCEPTED_FILE_TYPE, $file)) {
				foreach ($resources as $resource) {
					if ($resource->getPath() == $file) {
						$used = true;
						break;
					}
				}
			}
			if (!$used) {
				foreach ($defaultFiles as $defaultFile) {
					if ($defaultFile == $file) {
						$used = true;
						break;
					}
				}
			}
			if (!$used) {
				if ($verbose) {
					$output->writeln('<comment>[Unused]</comment>');
				}
				$unusedFiles[] = $file;
			} else {
				if ($verbose) {
					$output->writeln('<comment>[Used]</comment>');
				}
			}

		}

		foreach ($unusedFiles as $unusedFile) {
			if ($verbose) {
				$output->write('<info>Removing '.$unusedFile.' ... </info>');
			}
			if ($forced) {
				if (unlink($uploadDir.'/'.$unusedFile)) {
					if ($verbose) {
						$output->writeln('<fg=cyan>[Removed]</fg=cyan>');
					}
				} else if ($verbose) {
					$output->writeln('<fg=red>[Failed]</fg=red>');
				}
			} else if ($verbose) {
				$output->writeln('<fg=cyan>[Fake]</fg=cyan>');
			}
		}
		if ($forced) {
			$output->writeln('<info>'.count($unusedFiles).' files removed</info>');
		} else {
			$output->writeln('<info>'.count($unusedFiles).' files to remove</info>');
		}

        return Command::SUCCESS;

	}

}