<?php

namespace App\Command;

use App\Utils\EmbeddableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ResetStickersCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:reset:stickers')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force reseting')
			->setDescription('Reset stickers')
			->setHelp(<<<EOT
The <info>ladb:reset:stickers</info> command clear all stickers
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();
		$embaddableUtils = $this->getContainer()->get(EmbeddableUtils::class);

		$resetStickerCount = 0;

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 's' ))
			->from('App\Entity\Wonder\Creation', 'c')
			->innerJoin('c.sticker', 's')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$creations = array();
		}

		foreach ($creations as $creation) {
			$resetStickerCount++;
			$embaddableUtils->resetSticker($creation);
		}
		unset($creations);

		// Check plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 's' ))
			->from('App\Entity\Wonder\Plan', 'p')
			->innerJoin('p.sticker', 's')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$plans = array();
		}

		foreach ($plans as $plan) {
			$resetStickerCount++;
			$embaddableUtils->resetSticker($plan);
		}
		unset($plans);

		// Check workshops /////

		$output->writeln('<info>Checking workshops...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 's' ))
			->from('App\Entity\Wonder\Workshop', 'w')
			->innerJoin('w.sticker', 's')
		;

		try {
			$workshops = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$workshops = array();
		}

		foreach ($workshops as $workshop) {
			$resetStickerCount++;
			$embaddableUtils->resetSticker($workshop);
		}
		unset($workshops);

		// Check howtos /////

		$output->writeln('<info>Checking howtos...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h', 's' ))
			->from('App\Entity\Howto\Howto', 'h')
			->innerJoin('h.sticker', 's')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$howtos = array();
		}

		foreach ($howtos as $howto) {
			$resetStickerCount++;
			$embaddableUtils->resetSticker($howto);
		}
		unset($howtos);

		// Check articles /////

		$output->writeln('<info>Checking articles...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'a', 's' ))
			->from('App\Entity\Howto\Article', 'a')
			->innerJoin('a.sticker', 's')
		;

		try {
			$articles = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$articles = array();
		}

		foreach ($articles as $article) {
			$resetStickerCount++;
			$embaddableUtils->resetSticker($article);
		}
		unset($articles);

		// Cleanup /////

		$forced = $input->getOption('force');

		if ($forced) {
			if ($resetStickerCount > 0) {
				$om->flush();
			}
			$output->writeln('<info>'.$resetStickerCount.' stickers reset</info>');
		} else {
			$output->writeln('<info>'.$resetStickerCount.' stickers to reset</info>');
		}
	}

}