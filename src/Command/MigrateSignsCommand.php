<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Core\Vote;

class MigrateSignsCommand extends AbstractContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:signs')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate signs')
			->setHelp(<<<EOT
The <info>ladb:migrate:signs</info> command generate textures
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);

		// Retrieve Signs

		$output->write('<info>Resetting signs...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 's' ))
			->from('App\Entity\Knowledge\Value\Sign', 's')
		;

		try {
			$signs = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$signs = array();
		}

		$output->writeln('<comment> ['.count($signs).' $values]</comment>');

		foreach ($signs as $sign) {

			$output->write('<info>Processing '.$sign->getData().' ...</info>');

			$sign->setIsAffiliate(!empty($sign->getStore()));
			$data = $sign->getBrand();
			if ($sign->getIsAffiliate()) {
				$data .= ','.$sign->getStore();
			}
			$sign->setData($data);

			$output->writeln('<comment> [Done]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}