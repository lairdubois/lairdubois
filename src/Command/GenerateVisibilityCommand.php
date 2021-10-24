<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Model\HiddableInterface;

class GenerateVisibilityCommand extends AbstractContainerAwareCommand {

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

		$em = $this->getDoctrine()->getManager();

		$entityCount = 0;

		$entityCount += $this->_process('App\Entity\Blog\Post', $em, $output);
		$entityCount += $this->_process('App\Entity\Faq\Question', $em, $output);
		$entityCount += $this->_process('App\Entity\Find\Find', $em, $output);
		$entityCount += $this->_process('App\Entity\Howto\Howto', $em, $output);
		$entityCount += $this->_process('App\Entity\Wonder\Creation', $em, $output);
		$entityCount += $this->_process('App\Entity\Wonder\Plan', $em, $output);
		$entityCount += $this->_process('App\Entity\Wonder\Workshop', $em, $output);
		$entityCount += $this->_process('App\Entity\Qa\Question', $em, $output);
		$entityCount += $this->_process('App\Entity\Promotion\Graphic', $em, $output);

		if ($forced) {
			$em->flush();
			$output->writeln('<info>'.$entityCount.' generated</info>');
		} else {
			$output->writeln('<info>'.$entityCount.' to generate</info>');
		}

        return Command::SUCCESS;

	}

}