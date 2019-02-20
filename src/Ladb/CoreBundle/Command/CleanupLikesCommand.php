<?php

namespace Ladb\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\ActivityUtils;

class CleanupLikesCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:cleanup:likes')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force removing')
			->setDescription('Cleanup likes by removing doubles')
			->setHelp(<<<EOT
The <info>ladb:cleanup:blocks</info> Cleanup likes by removing doubles
EOT
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$activityUtils = $this->getContainer()->get(ActivityUtils::NAME);

		$sql = '
			SELECT count(id) as counter, entity_type, entity_id, user_id
			FROM tbl_core_like
			GROUP BY entity_type, entity_id, user_id
			HAVING count(id) > 1
			ORDER BY counter DESC
		';

		$stmt = $om->getConnection()->prepare($sql);
		$stmt->execute([]);

		$results = $stmt->fetchAll();

		$output->writeln(count($results).' likes en doublon !');

		foreach ($results as $result) {
			if ($result['counter'] > 1) {

				$output->write('entity_type='.$result['entity_type']. ' entity_id='.$result['entity_id']. ' user_id='.$result['user_id'].' <info>['.$result['counter'].' copies]</info>');

				// Retrieve User
				$user = $userRepository->findOneById($result['user_id']);
				if (is_null($user)) {
					$output->writeln('User not found (id='.$result['user_id'].')');
					continue;
				}

				$output->writeln(' user='.$user->getDisplayName());

				// Retrieve Likes
				$queryBuilder = $om->createQueryBuilder();
				$queryBuilder
					->select(array( 'l' ))
					->from('LadbCoreBundle:Core\Like', 'l')
					->where('l.entityType = :entityType')
					->andWhere('l.entityId = :entityId')
					->andWhere('l.user = :user')
					->setParameter('entityType', $result['entity_type'])
					->setParameter('entityId', $result['entity_id'])
					->setParameter('user', $user)
					->orderBy('l.id', 'ASC')
				;

				$likes = $queryBuilder->getQuery()->getResult();

				for ($i = 1; $i < count($likes); $i++) {

					$like = $likes[$i];

					$output->writeln(' Removing Like activities...');
					$activityUtils->deleteActivitiesByLike($like, $forced);

					$output->writeln(' Removing Like id='.$like->getId().'...');
					$om->remove($like);

				}

			}
		}

		if ($forced) {
			$om->flush();
		}

	}

}