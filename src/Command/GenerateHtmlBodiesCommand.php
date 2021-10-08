<?php

namespace App\Command;

use App\Model\BlockBodiedInterface;
use App\Model\BodiedInterface;
use App\Utils\FieldPreprocessorUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Utils\BlockBodiedUtils;

class GenerateHtmlBodiesCommand extends AbstractCommand {

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
		$filedProcessoUtils = $this->getContainer()->get(FieldPreprocessorUtils::class);

		$entityCount = 0;

		$entityCount += $this->_process('App\Entity\Message\Message', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Core\Comment', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Wonder\Creation', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Wonder\Workshop', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Wonder\Plan', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Blog\Post', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Faq\Question', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Promotion\Graphic', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Find\Find', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Howto\Howto', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Howto\Article', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Workflow\Workflow', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Qa\Question', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Qa\Answer', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Event\Event', $om, $output, $filedProcessoUtils, $om, $forced);
		$entityCount += $this->_process('App\Entity\Offer\Offer', $om, $output, $filedProcessoUtils, $om, $forced);

		if ($forced) {
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

        return Command::SUCCESS;

	}

}