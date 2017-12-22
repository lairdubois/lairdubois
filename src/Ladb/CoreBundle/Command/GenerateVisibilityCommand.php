<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Model\HiddableInterface;

class GenerateVisibilityCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:visibility')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate Visibility')
			->setHelp(<<<EOT
The <info>ladb:generate:visibility</info> command generate Visibility
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
			$entity->setVisibility($entity->getIsDraft() ? HiddableInterface::VISIBILITY_PRIVATE : HiddableInterface::VISIBILITY_PUBLIC);
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
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Creation', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Plan', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Workshop', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Qa\Question', $em, $output);
		$entityCount += $this->_process('LadbCoreBundle:Promotion\Graphic', $em, $output);

		if ($forced) {
			$em->flush();
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

	}

}