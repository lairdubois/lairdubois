<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Model\BlockBodiedInterface;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;

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

	private function _process($entityClass, $em, OutputInterface $output, $filedProcessoUtils, $om, $forced) {

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
			$filedProcessoUtils->preprocessFields($entity);
			$entityCount++;
		}

		if ($forced) {
			$om->flush();
		}
		unset($entities);

		return $entityCount;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$filedProcessoUtils = $this->getContainer()->get(FieldPreprocessorUtils::NAME);

		$entityCount = 0;

		$entityCount += $this->_process('LadbCoreBundle:Message\Message', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Core\Comment', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Creation', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Workshop', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Wonder\Plan', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Blog\Post', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Faq\Question', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Promotion\Graphic', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Find\Find', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Howto', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Howto\Article', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Workflow\Workflow', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Qa\Question', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Qa\Answer', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Event\Event', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('LadbCoreBundle:Offer\Offer', $om, $output, $filedProcessoUtils, $om, $forced);

		if ($forced) {
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

	}

}