<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;

class GenerateHtmlBodiesCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:htmlbodies')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate htmlbodies')
			->setHelp(<<<EOT
The <info>ladb:generate:htmlbodies</info> command generate htmlbodies
EOT
			);
	}

	private function _process($entityClass, $em, OutputInterface $output, $fieldPreprocessorUtils) {

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
			$fieldPreprocessorUtils->preprocessFields($entity);
			$entityCount++;
		}

		return $entityCount;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$fieldPreprocessorUtils = $this->getContainer()->get(FieldPreprocessorUtils::NAME);

		$entityCount = 0;

		$entityCount += $this->_process('LadbCoreBundle:Blog\Post', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Faq\Question', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Find\Find', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Howto', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Article', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Message\Message', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Creation', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Plan', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Workshop', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Biography', $om, $output, $fieldPreprocessorUtils);
		$entityCount += $this->_process('LadbCoreBundle:Comment', $om, $output, $fieldPreprocessorUtils);

		if ($forced) {
			$om->flush();
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

	}

}