<?php

namespace App\Command;

use App\Entity\Knowledge\Provider;
use App\Entity\Knowledge\Value\Integer;
use App\Utils\CommentableUtils;
use App\Utils\KnowledgeUtils;
use App\Utils\PropertyUtils;
use App\Utils\VotableUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateProvidersCommand extends AbstractCommand {

	private $toTransferCommentables = array();
	private $toTransferVotables = array();

	protected function configure() {
		$this
			->setName('ladb:migrate:providers')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate provider products and services')
			->setHelp(<<<EOT
The <info>ladb:migrate:providers</info> command migrate provider products and services
EOT
			);
	}

	private function _convertValues($provider, $oldValues, $field, $choices, $output) {

		$propertyUtils = $this->getContainer()->get(PropertyUtils::class);
		$knowledgeUtils = $this->getContainer()->get(KnowledgeUtils::class);

		foreach ($oldValues as $oldValue) {

			$newValue = new Integer();
			$newValue->setParentEntityType($oldValue->getParentEntityType());
			$newValue->setParentEntityId($oldValue->getParentEntityId());
			$newValue->setParentEntityField($oldValue->getParentEntityField());
			$newValue->setCreatedAt($oldValue->getCreatedAt());
			$newValue->setUpdatedAt($oldValue->getUpdatedAt());
			$newValue->setUser($oldValue->getUser());
			$newValue->setLegend($oldValue->getLegend());
			$newValue->setSourceType($oldValue->getSourceType());
			$newValue->setSource($oldValue->getSource());

			if ($oldValue->getCommentCount() > 0) {
				$output->writeln('<info> -> '.$oldValue->getCommentCount().' comments to transfer ...</info>');
				$this->toTransferCommentables[] = array( $oldValue, $newValue );
			}

			if ($oldValue->getVoteCount() > 0) {
				$output->writeln('<info> -> '.$oldValue->getVoteCount().' votes to transfer ...</info>');
				$this->toTransferVotables[] = array( $oldValue, $newValue );
			}

			$data = $oldValue->getData();
			foreach ($choices as $key => $tmpFieldChoice) {
				if (strcmp($data, $tmpFieldChoice) == 0) {

					$output->writeln('<comment> -> convert '.$data.' to '.$key.'</comment>');

					$newValue->setData($key);
					break;
				}
			}

			if (is_null($newValue->getData())) {
				$output->writeln('<error> -> Impossible to convert '.$data.' !</error>');
			} else {

				$propertyUtils->addValue($provider, $field.'_value', $newValue);

			}

		}

		$knowledgeUtils->updateKnowledgeField($provider, $field);

	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$commentableUtils = $this->getContainer()->get(CommentableUtils::class);
		$votableUtils = $this->getContainer()->get(VotableUtils::class);

		// Retrieve Providers

		$output->write('<info>Retrieve providers...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'p' ))
			->from('App\Entity\Knowledge\Provider', 'p')
		;

		try {
			$providers = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$providers = array();
		}

		$output->writeln('<comment> ['.count($providers).' providers]</comment>');

		$productChoices = array(0 => 'Bois massif', 1 => 'Bois panneaux', 2 => 'Bois placages', 3 => 'Outillage', 4 => 'Quincaillerie', 5 => 'Produits de finition', 6 => 'Collage et Fixation', 7 => 'Miroiterie');
		$serviceChoices = array(0 => 'Formations', 1 => 'Affûtage', 2 => 'Découpe');

		foreach ($providers as $provider) {

			$output->writeln('<info>Processing <fg=cyan>'.$provider->getTitle().'</fg=cyan> ...</info>');

			$output->writeln('<info>Migrate products ...</info>');
			$this->_convertValues($provider, $provider->getProductsOldValues(), Provider::FIELD_PRODUCTS, $productChoices, $output);

			$output->writeln('<info>Migrate services ...</info>');
			$this->_convertValues($provider, $provider->getServicesOldValues(), Provider::FIELD_SERVICES, $serviceChoices, $output);

			$output->writeln('<comment>[Done]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

		$output->writeln('<info>Transfer comments ...</info>');
		foreach ($this->toTransferCommentables as $toTransferCommentable) {
			$commentableUtils->transferComments($toTransferCommentable[0], $toTransferCommentable[1], false);
		}

		$output->writeln('<info>Transfer votes ...</info>');
		foreach ($this->toTransferVotables as $toTransferVotable) {
			$votableUtils->transferVotes($toTransferVotable[0], $toTransferVotable[1], false);
		}

		$output->writeln('<info>Clearing old values ...</info>');
		foreach ($providers as $provider) {
			$provider->getProductsOldValues()->clear();
			$provider->setProductsOld(null);
			$provider->getServicesOldValues()->clear();
			$provider->setServicesOld(null);
		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}