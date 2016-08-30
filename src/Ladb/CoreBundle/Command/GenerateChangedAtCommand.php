<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateChangedAtCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:changedat')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate ChangedAt')
			->setHelp(<<<EOT
The <info>ladb:generate:changedat</info> command generate ChangedAt
EOT
			);
	}

	private function _process($entityClass, $em, OutputInterface $output) {

		$output->write('<info>Retrieve '.$entityClass.'...</info>');

		$queryBuilder = $em->createQueryBuilder();
		$queryBuilder
			->select(array( 'e' ))
			->from($entityClass, 'e')
		;

		try {
			$entities = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$entities = array();
		}

		$output->writeln('<comment> ['.count($entities).' '.$entityClass.']</comment>');

		$entityCount = 0;
		foreach ($entities as $entity) {
			$entity->setChangedAt($entity->getCreatedAt());
			$entityCount++;
		}

		return $entityCount;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$em = $this->getContainer()->get('doctrine')->getManager();

		$entityCount = 0;

		$entityCount += $this->_process('LadbCoreBundle:Blog\Post', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Faq\Question', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Find\Find', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Howto', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Article', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Creation', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Plan', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Workshop', $em, $output);

		if ($forced) {
			$em->flush();
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

	}

}