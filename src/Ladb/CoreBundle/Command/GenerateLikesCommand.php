<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Utils\TypableUtils;

class GenerateLikesCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:generate:likes')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Generate likes')
			->setHelp(<<<EOT
The <info>ladb:generate:likes</info> command generate likes
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();

		// Retrieve users

		$output->write('<info>Resetting users...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'u' ))
			->from('LadbCoreBundle:Core\User', 'u')
		;

		try {
			$users = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$users = array();
		}

		$output->writeln('<comment> ['.count($users).' users]</comment>');

		foreach ($users as $user) {
			$user->incrementRecievedLikeCount(-$user->getRecievedLikeCount());
			$user->incrementSentLikeCount(-$user->getSentLikeCount());
		}

		// Retrive likes /////

		$output->writeln('<info>Retriveing likes...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'l', 'u' ))
			->from('LadbCoreBundle:Like', 'l')
			->innerJoin('l.user', 'u')
		;

		try {
			$likes = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$likes = array();
		}

		foreach ($likes as $like) {
			$like->getUser()->incrementSentLikeCount();
			$entityClassName = TypableUtils::getClassByType($like->getEntityType());
			if (!is_null($entityClassName)) {
				$entityRepository = $om->getRepository($entityClassName);
				$entity = $entityRepository->findOneById($like->getEntityId());
				if (!is_null($entity) and $entity instanceof AuthoredInterface) {
					$entity->getUser()->incrementRecievedLikeCount();
					$like->setEntityUser($entity->getUser());
				} else {
					$like->setEntityUser(null);
				}
			}
		}

		if ($forced) {
			$om->flush();
		}

	}

}