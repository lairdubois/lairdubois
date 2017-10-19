<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Knowledge\Value\IntegerOld;
use Ladb\CoreBundle\Entity\Knowledge\Value\PictureOld;
use Ladb\CoreBundle\Entity\Knowledge\Value\StringOld;
use Ladb\CoreBundle\Entity\Knowledge\WoodOld;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateWoodsCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:woods')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate woods')
			->setHelp(<<<EOT
The <info>ladb:migrate:users</info> command migrate woods
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$em = $this->getContainer()->get('doctrine')->getManager();
		$commentRepository = $em->getRepository(Comment::CLASS_NAME);

		// Retrieve users

		$output->write('<info>Retrieve woods...</info>');

		$queryBuilder = $em->createQueryBuilder();
		$queryBuilder
			->select(array( 'w' ))
			->from('LadbCoreBundle:Knowledge\WoodOld', 'w')
		;

		try {
			$woodOlds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$woodOlds = array();
		}

		$output->writeln('<info> ['.count($woodOlds).' woodOlds]</info>');

		foreach ($woodOlds as $woodOld) {
			$output->writeln('<info>'.$woodOld->getTitle().'</info>');

			$wood = new \Ladb\CoreBundle\Entity\Knowledge\Wood();

			// Value
			foreach (WoodOld::$FIELD_DEFS as $field => $fieldDef) {
				$output->writeln('<comment>Converting field '.$field.'...</comment>');

				$wood->{'set'.ucfirst($field)}($woodOld->{'get'.ucfirst($field)}());

			}

			$em->persist($wood);
			if ($forced) {
				$em->flush();
			}

			// Values
			foreach (WoodOld::$FIELD_DEFS as $field => $fieldDef) {

				$valueOlds = $woodOld->{ 'get'.ucfirst($field).'Values'}();
				foreach ($valueOlds as $valueOld) {
					switch ($fieldDef[\Ladb\CoreBundle\Entity\Knowledge\Wood::ATTRIB_TYPE]) {
						case StringOld::TYPE_STRIPPED_NAME:
							$value = new \Ladb\CoreBundle\Entity\Knowledge\Value\Text();
							break;
						case IntegerOld::TYPE_STRIPPED_NAME:
							$value = new \Ladb\CoreBundle\Entity\Knowledge\Value\Integer();
							break;
						case PictureOld::TYPE_STRIPPED_NAME:
							$value = new \Ladb\CoreBundle\Entity\Knowledge\Value\Picture();
							break;
						default:
							continue;
					}
					$value->setParentEntityType($valueOld->getParentEntityType());
					$value->setParentEntityId($wood->getId());
					$value->setParentEntityField($valueOld->getParentEntityField());
					$value->setCreatedAt($valueOld->getCreatedAt());
					$value->setUpdatedAt($valueOld->getUpdatedAt());
					$value->setUser($valueOld->getUser());
					$value->setLegend($valueOld->getLegend());
					$value->setSourceType($valueOld->getSourceType());
					$value->setSource($valueOld->getSource());
					$value->setPositiveVoteScore($valueOld->getPositiveVoteScore());
					$value->setNegativeVoteScore($valueOld->getNegativeVoteScore());
					$value->setVoteScore($valueOld->getVoteScore());
					$value->setData($valueOld->getData());
					$value->setCommentCount($valueOld->getCommentCount());

					$em->persist($value);
					if ($forced) {
						$em->flush();

						// Comments
						if ($valueOld->getCommentCount() > 0) {
							$output->writeln('<comment>['.$valueOld->getCommentCount().'] comments to transfer...</comment>');
							$comments = $commentRepository->findByEntityTypeAndEntityId($valueOld->getType(), $valueOld->getId());
							foreach ($comments as $comment) {
								$comment->setEntityId($value->getId());
							}
						}

						// Votes
						$votes = $valueOld->getVotes();
						if (count($valueOld->getVotes()) > 0) {
							$output->writeln('<comment>[' . count($valueOld->getVotes()) . '] votes to transfer...</comment>');
							foreach ($votes as $vote) {
								$vote->setEntityId($value->getId());
							}
						}

					}

					$wood->{ 'add'.ucfirst($field).'Value' }($value);
				}

			}

			$wood->setCreatedAt($woodOld->getCreatedAt());
			$wood->setChangedAt($woodOld->getChangedAt());
			$wood->setUpdatedAt($woodOld->getUpdatedAt());
			$wood->setIsDraft($woodOld->getIsDraft());

			$wood->setContributorCount($woodOld->getContributorCount());
			$wood->setPositiveVoteCount($woodOld->getPositiveVoteCount());
			$wood->setNegativeVoteCount($woodOld->getNegativeVoteCount());
			$wood->setVoteCount($woodOld->getVoteCount());
			$wood->setLikeCount($woodOld->getLikeCount());
			$wood->setWatchCount($woodOld->getWatchCount());
			$wood->setCommentCount($woodOld->getCommentCount());
			$wood->setViewCount($woodOld->getViewCount());

		}

		if ($forced) {
			$em->flush();
		}

	}

}