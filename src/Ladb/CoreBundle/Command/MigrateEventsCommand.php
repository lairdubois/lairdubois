<?php

namespace Ladb\CoreBundle\Command;

use Ladb\CoreBundle\Entity\Event\Event;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Manager\Find\FindManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\JoinableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\ReportableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ladb\CoreBundle\Entity\Core\Block\Text;

class MigrateEventsCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName('ladb:migrate:events')
			->addOption('force', null, InputOption::VALUE_NONE, 'Force updating')
			->setDescription('Migrate find body to body blocks')
			->setHelp(<<<EOT
The <info>ladb:migrate:providers</info> command migrate find body to body blocks
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$forced = $input->getOption('force');

		$om = $this->getContainer()->get('doctrine')->getManager();
		$fieldPreprocessorUtils = $this->getContainer()->get(FieldPreprocessorUtils::NAME);

		// Retrieve Finds

		$output->write('<info>Retrieve finds...</info>');

		$queryBuilder = $om->createQueryBuilder();
		$queryBuilder
			->select(array( 'f' ))
			->from('LadbCoreBundle:Find\Find', 'f')
			->where('f.kind = :kind')
			->setParameter('kind', Find::KIND_EVENT)
		;

		try {
			$finds = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			$finds = array();
		}

		$output->writeln('<comment> ['.count($finds).' finds]</comment>');

		foreach ($finds as $find) {

			$output->writeln('<info>Processing <fg=cyan>'.$find->getTitle().'</fg=cyan> ...</info>');

			$event = new Event();
			$event->setTitle($find->getTitle());
			$event->setCreatedAt($find->getCreatedAt());
			$event->setUpdatedAt($find->getUpdatedAt());
			$event->setChangedAt($find->getChangedAt());
			$event->setVisibility($find->getVisibility());
			$event->setIsDraft($find->getIsDraft());
			$event->setTitle($find->getTitle());
			$event->setUser($find->getUser());
			$event->setMainPicture($find->getMainPicture());

			$event->setLocation($find->getContent()->getLocation());
			$event->setLatitude($find->getContent()->getLatitude());
			$event->setLongitude($find->getContent()->getLongitude());

			$event->setStartAt($find->getContent()->getStartAt());
			$event->setStartDate($find->getContent()->getStartDate());
			$event->setStartTime($find->getContent()->getStartTime());
			$event->setEndAt($find->getContent()->getEndAt());
			$event->setEndDate($find->getContent()->getEndDate());
			$event->setEndTime($find->getContent()->getEndTime());

			$event->setUrl($find->getContent()->getUrl());
			$event->setCancelled($find->getContent()->getCancelled());

			$blockBodiedUtils = $this->getContainer()->get(BlockBodiedUtils::NAME);
			$blockBodiedUtils->copyBlocksTo($find, $event);

			foreach ($find->getContent()->getPictures() as $picture) {
				$event->addPicture($picture);
			}

			foreach ($find->getTags() as $tag) {
				$event->addTag($tag);
			}

			// Setup event's htmlBody
			$fieldPreprocessorUtils = $this->getContainer()->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($event);

			if ($forced) {
				// Persist event to generate ID
				$om->persist($event);
				$om->flush();
			}

			// Dispatch publications event
			$dispatcher = $this->getContainer()->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED_FROM_CONVERT, new PublicationEvent($event));

			// User counter
			if ($event->getIsDraft()) {
				$event->getUser()->getMeta()->incrementPrivateFindCount(1);
			} else {
				$event->getUser()->getMeta()->incrementPublicFindCount(1);
			}

			// Transfer views
			$viewableUtils = $this->getContainer()->get(ViewableUtils::NAME);
			$viewableUtils->transferViews($find, $event, false);

			// Transfer likes
			$likableUtils = $this->getContainer()->get(LikableUtils::NAME);
			$likableUtils->transferLikes($find, $event, false);

			// Transfer comments
			$commentableUtils = $this->getContainer()->get(CommentableUtils::NAME);
			$commentableUtils->transferComments($find, $event, false);

			// Transfer watches
			$watchableUtils = $this->getContainer()->get(WatchableUtils::NAME);
			$watchableUtils->transferWatches($find, $event, false);

			// Transfer joins
			$joinableUtils = $this->getContainer()->get(JoinableUtils::NAME);
			$joinableUtils->transferJoins($find, $event, false);

			// transfer reports
			$reportableUtils = $this->getContainer()->get(ReportableUtils::NAME);
			$reportableUtils->transferReports($find, $event, false);

			// Transfer publish activities
			$activityUtils = $this->getContainer()->get(ActivityUtils::NAME);
			$activityUtils->transferPublishActivities($find->getType(), $find->getId(), $event->getType(), $event->getId(), false);

			// Create the witness
			$witnessManager = $this->getContainer()->get(WitnessManager::NAME);
			$witnessManager->createConvertedByPublication($find, $event, false);

			// Delete the find
			$findManager = $this->getContainer()->get(FindManager::NAME);
			$findManager->delete($find, false, false);

			$output->writeln('<comment>[Done]</comment>');

		}

		if ($forced) {
			$om->flush();
		}

	}

}