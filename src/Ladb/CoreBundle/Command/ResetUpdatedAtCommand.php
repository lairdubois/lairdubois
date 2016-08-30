<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ResetUpdatedAtCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:reset:updatedat')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Reset updatedAt')
			->setHelp(<<<EOT
The <info>ladb:reset:updatedat</info> command reset updatedAt
EOT
			);
	}

	private function _reset($entityClass, $em, OutputInterface $output) {

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

		foreach ($entities as $entity) {
			$entity->setUpdatedAt(null);
		}

		return count($entities);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$em = $this->getContainer()->get('doctrine')->getManager();

		$entityCount = 0;

		$entityCount += $this->_reset('LadbCoreBundle:Blog\Post', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Faq\Question', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Find\Find', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Howto\Howto', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Howto\Article', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Wonder\Creation', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Wonder\Plan', $em, $output);
		$entityCount += $this->_reset('LadbCoreBundle:Wonder\Workshop', $em, $output);

		if ($forced) {
			$em->flush();
			$output->writeln('<info>'.$entityCount.' reseted</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to reseted</info>');
		}

	}

}