<?php

namespace App\Command;

use App\Entity\Core\TagUsage;
use App\Model\TaggableInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTagUsagesCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:tagusages')
			->setDescription('Generate TagUsages')
			->setHelp(<<<EOT
The <info>ladb:generate:tagusages</info> command generate tag usages
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Check creations /////

		$output->writeln('<info>Checking creations...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 't' ))
			->from('App\Entity\Wonder\Creation', 'c')
			->leftJoin('c.tags', 't')
		;

		try {
			$creations = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($creations as $creation) {
			$this->_generateTagUsages($creation);
		}

		// Check Plans /////

		$output->writeln('<info>Checking plans...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 't' ))
			->from('App\Entity\Wonder\Plan', 'p')
			->leftJoin('p.tags', 't')
		;

		try {
			$plans = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($plans as $plan) {
			$this->_generateTagUsages($plan);
		}

		// Check Workshops /////

		$output->writeln('<info>Checking workshops...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'w', 't' ))
			->from('App\Entity\Wonder\Workshop', 'w')
			->leftJoin('w.tags', 't')
		;

		try {
			$workshops = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($workshops as $workshop) {
			$this->_generateTagUsages($workshop);
		}

		// Check Howtos /////

		$output->writeln('<info>Checking howtos...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'h', 't' ))
			->from('App\Entity\Howto\Howto', 'h')
			->leftJoin('h.tags', 't')
		;

		try {
			$howtos = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($howtos as $howto) {
			$this->_generateTagUsages($howto);
		}

		// Check Finds /////

		$output->writeln('<info>Checking finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f', 't' ))
			->from('App\Entity\Find\Find', 'f')
			->leftJoin('f.tags', 't')
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($finds as $find) {
			$this->_generateTagUsages($find);
		}

		// Check Posts /////

		$output->writeln('<info>Checking posts...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p', 't' ))
			->from('App\Entity\Blog\Post', 'p')
			->leftJoin('p.tags', 't')
		;

		try {
			$posts = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return Command::FAILURE;
		}

		foreach ($posts as $post) {
			$this->_generateTagUsages($post);
		}

        return Command::SUCCESS;

	}

	private function _generateTagUsages(TaggableInterface $taggable) {
		$om = $this->getContainer()->get('doctrine')->getManager();
		$tagUsageRepository = $om->getRepository(TagUsage::CLASS_NAME);
		foreach ($taggable->getTags() as $tag) {
			$tagUsage = $tagUsageRepository->findOneByTagAndEntityType($tag, $taggable->getType());
			if (is_null($tagUsage)) {
				$tagUsage = new TagUsage();
				$tagUsage->setTag($tag);
				$tagUsage->setEntityType($taggable->getType());
				$om->persist($tagUsage);
			}
			$tagUsage->incrementScore();
			$om->flush();
		}
	}

}