<?php

namespace App\Command;

use App\Model\HiddableInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Utils\PropertyUtils;

class GenerateContributionCountersCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:contributioncounters')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate contribution counters')
			->setHelp(<<<EOT
The <info>ladb:generate:contributioncounters</info> command generate contribution counters
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');
		$verbose = $input->getOption('verbose');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$propertyUtils = $this->getContainer()->get(PropertyUtils::class);

		$defs = array(
			array(
				'className' => \App\Entity\Core\Comment::CLASS_NAME,
				'name'      => 'comment',
				'hiddable'	=> false,
			),
			array(
				'className' => \App\Entity\Wonder\Creation::CLASS_NAME,
				'name'      => 'creation',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Wonder\Plan::CLASS_NAME,
				'name'      => 'plan',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Howto\Howto::CLASS_NAME,
				'name'      => 'howto',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Wonder\Workshop::CLASS_NAME,
				'name'      => 'workshop',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Find\Find::CLASS_NAME,
				'name'      => 'find',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Event\Event::CLASS_NAME,
				'name'      => 'event',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Qa\Question::CLASS_NAME,
				'name'      => 'question',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Qa\Answer::CLASS_NAME,
				'name'      => 'answer',
				'hiddable'	=> false,
			),
			array(
				'className' => \App\Entity\Offer\Offer::CLASS_NAME,
				'name'      => 'offer',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Promotion\Graphic::CLASS_NAME,
				'name'      => 'graphic',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Workflow\Workflow::CLASS_NAME,
				'name'      => 'workflow',
				'hiddable'	=> true,
			),
			array(
				'className' => \App\Entity\Collection\Collection::CLASS_NAME,
				'name'      => 'collection',
				'hiddable'	=> true,
			),

			array(
				'className' => \App\Entity\Knowledge\Value\BaseValue::CLASS_NAME,
				'name'      => 'proposal',
				'hiddable'	=> false,
			),
			array(
				'className' => \App\Entity\Knowledge\School\Testimonial::CLASS_NAME,
				'name'      => 'testimonial',
				'hiddable'	=> false,
			),
			array(
				'className' => \App\Entity\Core\Review::CLASS_NAME,
				'name'      => 'review',
				'hiddable'	=> false,
			),
		);

		// Retrieve Users

		$output->writeln('<info>Retrieve users...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'u' ))
			->from('App\Entity\Core\User', 'u')
		;

		try {
			$users = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$users = array();
		}

		$output->writeln('<comment> ['.count($users).' users]</comment>');

		foreach ($users as $user) {

			if ($verbose) {
				$output->writeln('<info> - Process user='.$user->getDisplayName().'...</info>');
			}

			$userMeta = $user->getMeta();
			$contributionCount = 0;

			foreach ($defs as $def) {

				// Compute counters
				$counters = $this->_computeEntitiesCountersByUser($def['className'], $def['name'], $def['hiddable'], $user, $forced, $verbose, $output);

				// Set counter value
				if ($def['hiddable']) {
					$propertyUtils->setValue($userMeta, 'private_'.$def['name'].'_count', $counters['private']);
					$propertyUtils->setValue($userMeta, 'public_'.$def['name'].'_count', $counters['public']);
				} else {
					$propertyUtils->setValue($userMeta, $def['name'].'_count', $counters['public']);
				}

				if ($verbose) {
					$output->writeln(' <comment>private='.$counters['private'].' public='.$counters['public'].'</comment>');
				}

				// Increment contribution counter
				$contributionCount += $counters['public'];

			}

			// Set contibution value
			$propertyUtils->setValue($userMeta, 'contribution_count', $contributionCount);

			if ($verbose) {
				$output->writeln('<comment> -- contributionCount='.$contributionCount.'</comment>');
			}
		}

		if ($forced) {
			$om->flush();
		}

	}

	/////

	private function _computeEntitiesCountersByUser($entityClassName, $entityName, $hiddable, $user, $forced, $verbose, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

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