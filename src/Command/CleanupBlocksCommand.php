<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupBlocksCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:blocks')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup blocks')
			->setHelp(<<<EOT
The <info>ladb:cleanup:blocks</info> command remove unused blocks
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getDoctrine()->getManager();

		// Extract bodyBlocks /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'b' ))
			->from('App\Entity\Block\AbstractBlock', 'b')
		;

		try {
			$bodyBlocks = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$bodyBlocks = array();
		}

		$bodyblockCounters = array();
		foreach ($bodyBlocks as $bodyblock) {
			$bodyblockCounters[$bodyblock->getId()] = array( $bodyblock, 0 );
		}

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'bb' ))
			->from('App\Entity\Wonder\Creation', 'e')
			->leftJoin('e.bodyBlocks', 'bb')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		foreach ($entities as $entity) {
			foreach ($entity->getBodyBlocks() as $bodyblock) {
				$bodyblockCounters[$bodyblock->getId()][1]++;
			}
		}

		// Check workshops /////

		$output->writeln('<info>Checking workshops...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'bb' ))
			->from('App\Entity\Wonder\Workshop', 'e')
			->leftJoin('e.bodyBlocks', 'bb')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		foreach ($entities as $entity) {
			foreach ($entity->getBodyBlocks() as $bodyblock) {
				$bodyblockCounters[$bodyblock->getId()][1]++;
			}
		}

		// Check posts /////

		$output->writeln('<info>Checking posts...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'bb' ))
			->from('App\Entity\Blog\Post', 'e')
			->leftJoin('e.bodyBlocks', 'bb')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		foreach ($entities as $entity) {
			foreach ($entity->getBodyBlocks() as $bodyblock) {
				$bodyblockCounters[$bodyblock->getId()][1]++;
			}
		}

		// Check questions /////

		$output->writeln('<info>Checking questions...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'bb' ))
			->from('App\Entity\Faq\Question', 'e')
			->leftJoin('e.bodyBlocks', 'bb')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		foreach ($entities as $entity) {
			foreach ($entity->getBodyBlocks() as $bodyblock) {
				$bodyblockCounters[$bodyblock->getId()][1]++;
			}
		}

		// Check articles /////

		$output->writeln('<info>Checking articles...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e', 'bb' ))
			->from('App\Entity\Howto\Article', 'e')
			->leftJoin('e.bodyBlocks', 'bb')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		foreach ($entities as $entity) {
			foreach ($entity->getBodyBlocks() as $bodyblock) {
				$bodyblockCounters[$bodyblock->getId()][1]++;
			}
		}

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedBodyBlockCount = 0;
		foreach ($bodyblockCounters as $bodyblockCounter) {
			$counter = $bodyblockCounter[1];
			if ($counter == 0) {
				$unusedBodyBlockCount++;
				$bodyblock = $bodyblockCounter[0];
				if ($verbose) {
					$output->writeln('<info> -> "'.$bodyblock->getId().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($bodyblock);
				}
			}
		}

		if ($forced) {
			if ($unusedBodyBlockCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedBodyBlockCount.' bodyBlocks removed</info>');
		} else {
			$output->writeln('<info>'.$unusedBodyBlockCount.' bodyBlocks to remove</info>');
		}

        return Command::SUCCESS;
	}

}