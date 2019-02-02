<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Core\Review;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateReviewsCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:reviews')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate book reviews to generic reviews')
			->setHelp(<<<EOT
The <info>ladb:migrate:providers</info> command migrate book reviews to generic reviews
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$activityUtils = $this->getContainer()->get(ActivityUtils::NAME);

		// Retrieve Finds

		$output->write('<info>Retrieve reviews...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'r' ))
			->from('LadbCoreBundle:Knowledge\Book\Review', 'r')
		;

		try {
			$oldReviews = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$oldReviews = array();
		}

		$output->writeln('<comment> ['.count($oldReviews).' reviews]</comment>');

		foreach ($oldReviews as $oldReview) {

			$book = $oldReview->getBook();

			$output->writeln('<info>Processing <fg=cyan>'.$book->getTitle().'</fg=cyan> ...</info>');

			// Create a new review

			$review = new Review();
			$review->setEntityType($book->getType());
			$review->setEntityId($book->getId());
			$review->setUser($oldReview->getUser());
			$review->setCreatedAt($oldReview->getCreatedAt());
			$review->setUpdatedAt($oldReview->getUpdatedAt());
			$review->setTitle($oldReview->getTitle());
			$review->setBody($oldReview->getBody());
			$review->setHtmlBody($oldReview->getHtmlBody());
			$review->setRating($oldReview->getRating());

			$om->persist($review);

			// Delete old review

			$activityUtils->deleteActivitiesByOldReview($oldReview, false);
			$om->remove($oldReview);

			$output->writeln('<comment>[Done]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

	}

}