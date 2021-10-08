<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\TagUsage;
use App\Entity\Find\Find;
use App\Entity\Howto\Howto;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Entity\Blog\Post;

class CleanupTagsCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:tags')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup tags')
			->setHelp(<<<EOT
The <info>ladb:cleanup:tags</info> command remove unused tags
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();
		$tagUsageRepository = $om->getRepository(TagUsage::CLASS_NAME);

		// Extract tags /////

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 't' ))
			->from('App\Entity\Tag', 't')
		;

		try {
			$tags = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		$tagCounters = array();
		foreach ($tags as $tag) {
			$tagCounters[$tag->getId()] = array( $tag, 0, array( Creation::TYPE => 0, Plan::TYPE => 0, Workshop::TYPE => 0, Howto::TYPE => 0, Find::TYPE => 0, Post::TYPE => 0 ) );
		}

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 't' ))
			->from('App\Entity\Wonder\Creation', 'c')
			->leftJoin('c.tags', 't')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($creations as $creation) {
			foreach ($creation->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Creation::TYPE]++;
			}
		}

		// Check plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 't' ))
			->from('App\Entity\Wonder\Plan', 'p')
			->leftJoin('p.tags', 't')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($plans as $plan) {
			foreach ($plan->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Plan::TYPE]++;
			}
		}

		// Check workshop /////

		$output->writeln('<info>Checking workshop...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 't' ))
			->from('App\Entity\Wonder\Workshop', 'w')
			->leftJoin('w.tags', 't')
		;

		try {
			$workshops = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($workshops as $workshop) {
			foreach ($workshop->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Workshop::TYPE]++;
			}
		}

		// Check howtos /////

		$output->writeln('<info>Checking howtos...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h', 't' ))
			->from('App\Entity\Howto\Howto', 'h')
			->leftJoin('h.tags', 't')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($howtos as $howto) {
			foreach ($howto->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Howto::TYPE]++;
			}
		}

		// Check finds /////

		$output->writeln('<info>Checking finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 't' ))
			->from('App\Entity\Find\Find', 'f')
			->leftJoin('f.tags', 't')
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($finds as $find) {
			foreach ($find->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Find::TYPE]++;
			}
		}

		// Check posts /////

		$output->writeln('<info>Checking posts...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 't' ))
			->from('App\Entity\Blog\Post', 'p')
			->leftJoin('p.tags', 't')
		;

		try {
			$posts = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($posts as $post) {
			foreach ($post->getTags() as $tag) {
				$tagCounters[$tag->getId()][1]++;
				$tagCounters[$tag->getId()][2][Post::TYPE]++;
			}
		}

		// Cleanup /////

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');
		$unusedTagCount = 0;
		$unusedTagUsageCount = 0;
		foreach ($tagCounters as $tagCounter) {
			$tag = $tagCounter[0];
			$counter = $tagCounter[1];
			$entityTypeCounters = $tagCounter[2];
			if ($counter == 0) {
				$unusedTagCount++;
				if ($verbose) {
					$output->writeln('<info> -> "'.$tag->getLabel().'" is unused</info>');
				}
				if ($forced) {
					$om->remove($tag);
				}
			} else {
				foreach ($entityTypeCounters as $entityType => $entityTypeCounter) {
					if ($entityTypeCounter == 0) {
						$tagUsage = $tagUsageRepository->findOneByTagAndEntityType($tag, $entityType);
						if (!is_null($tagUsage)) {
							$unusedTagUsageCount++;
							if ($verbose) {
								$output->writeln('<info> -> "'.$tag->getLabel().'" is unused for entityType='.$entityType.'</info>');
							}
							if ($forced) {
								$om->remove($tagUsage);
							}
						}
					}
				}
			}
		}

		if ($forced) {
			if ($unusedTagCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$unusedTagCount.' tags removed</info>');
			$output->writeln('<info>'.$unusedTagUsageCount.' tagUsages removed</info>');
		} else {
			$output->writeln('<info>'.$unusedTagCount.' tags to remove</info>');
			$output->writeln('<info>'.$unusedTagUsageCount.' tagUsages to remove</info>');
		}

        return Command::SUCCESS;
	}

}