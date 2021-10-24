<?php

namespace App\Command;

use App\Entity\Knowledge\Book;
use App\Entity\Knowledge\Value\BookIdentity;
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

class MigrateBooksCommand extends AbstractContainerAwareCommand {

	private $toTransferCommentables = array();
	private $toTransferVotables = array();

	protected function configure() {
		$this
			->setName('ladb:migrate:books')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate books title')
			->setHelp(<<<EOT
The <info>ladb:migrate:books</info> command migrate books title
EOT
			);
	}

	private function _convertValues($book, $oldValues, $output) {

		$knowledgeUtils = $this->get(KnowledgeUtils::class);

		foreach ($oldValues as $oldValue) {

			$newValue = new BookIdentity();
			$newValue->setParentEntityType($oldValue->getParentEntityType());
			$newValue->setParentEntityId($oldValue->getParentEntityId());
			$newValue->setParentEntityField(Book::FIELD_IDENTITY);
			$newValue->setCreatedAt($oldValue->getCreatedAt());
			$newValue->setUpdatedAt($oldValue->getUpdatedAt());
			$newValue->setUser($oldValue->getUser());
			$newValue->setLegend($oldValue->getLegend());
			$newValue->setSourceType($oldValue->getSourceType());
			$newValue->setSource($oldValue->getSource());

			$output->writeln('<info> data -> '.$oldValue->getData().' ...</info>');

			$newValue->setData($oldValue->getData());
			$newValue->setWork($newValue->getData());

			if ($oldValue->getCommentCount() > 0) {
				$output->writeln('<info> -> '.$oldValue->getCommentCount().' comments to transfer ...</info>');
				$this->toTransferCommentables[] = array( $oldValue, $newValue );
			}

			if ($oldValue->getVoteCount() > 0) {
				$output->writeln('<info> -> '.$oldValue->getVoteCount().' votes to transfer ...</info>');
				$this->toTransferVotables[] = array( $oldValue, $newValue );
			}

			$book->addIdentityValue($newValue);

		}

		$knowledgeUtils->updateKnowledgeField($book, Book::FIELD_IDENTITY);

	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getDoctrine()->getManager();
		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);

		// Retrieve Books

		$output->write('<info>Retrieve books...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'b' ))
			->from('App\Entity\Knowledge\Book', 'b')
		;

		try {
			$books = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$books = array();
		}

		$output->writeln('<comment> ['.count($books).' books]</comment>');

		foreach ($books as $book) {

			$output->writeln('<info>Processing <fg=cyan>'.$book->getTitle().'</fg=cyan> ...</info>');

			$this->_convertValues($book, $book->getTitleValues(), $output);

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
		foreach ($books as $book) {
			$book->getTitleValues()->clear();
		}

		if ($forced) {
			$om->flush();
		}

        return Command::SUCCESS;

	}

}