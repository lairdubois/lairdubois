<?php

namespace App\Command;

use App\Model\HiddableInterface;
use App\Utils\KnowledgeUtils;
use App\Utils\TypableUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Utils\PropertyUtils;

class GenerateCompletionCountersCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:completioncounters')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate completion counters')
			->setHelp(<<<EOT
The <info>ladb:generate:completioncounters</info> command generate completion counters
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getDoctrine()->getManager();
		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		$defs = array(
			array(
				'className' => \App\Entity\Knowledge\Book::class,
			),
			array(
				'className' => \App\Entity\Knowledge\Provider::class,
			),
			array(
				'className' => \App\Entity\Knowledge\School::class,
			),
			array(
				'className' => \App\Entity\Knowledge\Software::class,
			),
			array(
				'className' => \App\Entity\Knowledge\Wood::class,
			),
		);

		foreach ($defs as $def) {

			$entityRepository = $om->getRepository($def['className']);
			$entities = $entityRepository->findAll();

			foreach ($entities as $entity) {

				if ($verbose) {
					$output->write(' <info>'.$entity->getTitle().'...</info>');
				}

				$knowledgeUtils->computeCompletionPercent($entity);

				if ($verbose) {
					$output->writeln(' <comment> ['.$entity->getCompletion100().'%]</comment>');
				}

			}

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

	/////

	private function _computeEntitiesCountersByUser($entityClassName, $entityName, $hiddable, $user, $forced, $verbose, OutputInterface $output) {

		$om = $this->getDoctrine()->getManager();

		// Retrieve Entities

		if ($verbose) {
			$output->write('<info> -- Retrieve '.$entityName.'... </info>');
		}

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($entityClassName, 'e')
			->where('e.user = :user')
			->setParameter('user', $user)
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		$counters = array(
			'private' => 0,
			'public' => 0,
		);
		foreach ($entities as $entity) {
			if ($hiddable && $entity->getIsPrivate()) {
				$counters['private']++;
			} else {
				$counters['public']++;
			}
		}

		return $counters;
	}

}
