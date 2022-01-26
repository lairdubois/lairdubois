<?php

namespace Ladb\CoreBundle\Event;

use Ladb\CoreBundle\Entity\AbstractDraftableAuthoredPublication;
use Ladb\CoreBundle\Model\FeedbackableInterface;
use Ladb\CoreBundle\Model\LinkedToInterface;
use Ladb\CoreBundle\Model\MentionSourceInterface;
use Ladb\CoreBundle\Model\RepublishableInterface;
use Ladb\CoreBundle\Utils\FeedbackableUtils;
use Ladb\CoreBundle\Utils\MentionUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ladb\CoreBundle\Utils\UserUtils;
use Ladb\CoreBundle\Utils\OpenGraphUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\ViewableUtils;
use Ladb\CoreBundle\Utils\GlobalUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Model\ScrapableInterface;
use Ladb\CoreBundle\Model\PublicationInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Model\TaggableInterface;
use Ladb\CoreBundle\Model\WatchableInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\PicturedInterface;
use Ladb\CoreBundle\Model\MultiPicturedInterface;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Entity\AbstractPublication;

class PublicationListener implements EventSubscriberInterface {

	const PUBLICATION_CREATED = 'ladb.publication.created';
	const PUBLICATION_CREATED_FROM_CONVERT = 'ladb.publication.created_from_convert';
	const PUBLICATION_CHANGED = 'ladb.publication.changed';
	const PUBLICATION_UPDATED = 'ladb.publication.updated';
	const PUBLICATION_DELETED = 'ladb.publication.deleted';
	const PUBLICATION_LOCKED = 'ladb.publication.locked';
	const PUBLICATION_UNLOCKED = 'ladb.publication.unlocked';
	const PUBLICATION_PUBLISHED = 'ladb.publication.published';
	const PUBLICATION_UNPUBLISHED = 'ladb.publication.unpublished';
	const PUBLICATION_SHOWN = 'ladb.publication.shown';
	const PUBLICATIONS_LISTED = 'ladb.publications.listed';

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public static function getSubscribedEvents() {
		return array(
			PublicationListener::PUBLICATION_CREATED              => 'onPublicationCreated',
			PublicationListener::PUBLICATION_CREATED_FROM_CONVERT => 'onPublicationCreatedFromConvert',
			PublicationListener::PUBLICATION_CHANGED              => 'onPublicationChanged',
			PublicationListener::PUBLICATION_UPDATED              => 'onPublicationUpdated',
			PublicationListener::PUBLICATION_DELETED              => 'onPublicationDeleted',
			PublicationListener::PUBLICATION_LOCKED               => 'onPublicationLocked',
			PublicationListener::PUBLICATION_UNLOCKED             => 'onPublicationUnlocked',
			PublicationListener::PUBLICATION_PUBLISHED            => 'onPublicationPublished',
			PublicationListener::PUBLICATION_UNPUBLISHED          => 'onPublicationUnpublished',
			PublicationListener::PUBLICATION_SHOWN                => 'onPublicationShown',
			PublicationListener::PUBLICATIONS_LISTED              => 'onPublicationsListed',
		);
	}

	/////

	private function _resolvePicturesPageImageFilter(AbstractPublication $publication) {

		$filter = '470x275o';
		if ($publication instanceof MultiPicturedInterface) {
			$pictures = $publication->getPictures();
		} else if ($publication instanceof PicturedInterface) {
			$pictures = array( $publication->getMainPicture() );
		} else {
			return;
		}

		$filterManager = $this->container->get('liip_imagine.filter.manager');
		$cacheManager = $this->container->get('liip_imagine.cache.manager');
		$dataManager = $this->container->get('liip_imagine.data.manager');

		foreach ($pictures as $picture) {
			if (is_null($picture)) {
				continue;
			}
			$path = $picture->getPath();
			if (!$cacheManager->isStored($path, $filter)) {
				$binary = $dataManager->find($filter, $path);
				$cacheManager->store(
					$filterManager->applyFilter($binary, $filter),
					$path,
					$filter
				);
			}
		}

	}

	private function _scrapeOpenGraph(AbstractPublication $publication) {
		if ($this->container->get(GlobalUtils::NAME)->getDebug()) {
			return;
		}
		if (!($publication instanceof ScrapableInterface) || !$publication->getIsScrapable()) {
			return;
		}

		// Scrape Open Graph URL (canonical)
		$openGraphUtils = $this->container->get(OpenGraphUtils::NAME);
		$openGraphUtils->scrape($this->container->get(TypableUtils::NAME)->getUrlAction($publication, 'show', true, false));

	}

	/////

	public function onPublicationCreated(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof TaggableInterface) {

			// Tags usage
			$tagUtils = $this->container->get(TagUtils::NAME);
			$tagUtils->useTaggableTags($publication);

		}

		if ($publication instanceof WatchableInterface) {

			// Auto watch
			$watchableUtils = $this->container->get(WatchableUtils::NAME);
			$watchableUtils->autoCreateWatch($publication, $this->container->get(GlobalUtils::NAME)->getUser());

		}

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->insertEntityToIndex($publication);

		}

		// Resolve main picture to avoid image url redirection
		$this->_resolvePicturesPageImageFilter($publication);

		// Scrape Open Graph URL
		$this->_scrapeOpenGraph($publication);

	}

	public function onPublicationCreatedFromConvert(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof MentionSourceInterface) {

			// Process mentions
			$mentionUtils = $this->container->get(MentionUtils::NAME);
			$mentionUtils->processMentions($publication);

		}

		if ($publication instanceof TaggableInterface) {

			// Tags usage
			$tagUtils = $this->container->get(TagUtils::NAME);
			$tagUtils->useTaggableTags($publication);

		}

		// Search index update
		$searchUtils = $this->container->get(SearchUtils::NAME);
		$searchUtils->insertEntityToIndex($publication);

		// Resolve main picture to avoid image url redirection
		$this->_resolvePicturesPageImageFilter($publication);

		// Scrape Open Graph URL
		$this->_scrapeOpenGraph($publication);

	}

	public function onPublicationChanged(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof ViewableInterface) {

			// Delete listed views
			$viewableUtils = $this->container->get(ViewableUtils::NAME);
			$viewableUtils->deleteViews($publication);

		}

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($publication);

		}

	}

	public function onPublicationUpdated(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof MentionSourceInterface) {

			// Process mentions
			$mentionUtils = $this->container->get(MentionUtils::NAME);
			$mentionUtils->processMentions($publication);

		}

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($publication);

			// Linked entities update
			if ($publication instanceof LinkedToInterface) {
				foreach ($publication->getLinkedEntities() as $entity) {
					if ($entity instanceof IndexableInterface && $entity->isIndexable()) {

						// Search index update
						$searchUtils->replaceEntityInIndex($entity);

					}
				}
			}

		}

		if ($publication instanceof TaggableInterface && array_key_exists('previouslyUsedTags', $event->getData())) {

			// Tags usage
			$tagUtils = $this->container->get(TagUtils::NAME);
			$tagUtils->useTaggableTags($publication, $event->getData()['previouslyUsedTags']);

		}

		// Resolve pictures
		$this->_resolvePicturesPageImageFilter($publication);

		// Scrape Open Graph URL
		$this->_scrapeOpenGraph($publication);

	}

	public function onPublicationDeleted(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->deleteEntityFromIndex($publication);

			// Linked entities update
			if ($publication instanceof LinkedToInterface) {
				foreach ($publication->getLinkedEntities() as $entity) {
					if ($entity instanceof IndexableInterface && $entity->isIndexable()) {

						// Search index update
						$searchUtils->replaceEntityInIndex($entity);

					}
				}
			}

		}

		if ($publication->getNotificationStrategy() != PublicationInterface::NOTIFICATION_STRATEGY_NONE) {

			// Delete activity
			$activityUtils = $this->container->get(ActivityUtils::NAME);
			$activityUtils->deleteActivitiesByEntityTypeAndEntityId($publication->getType(), $publication->getId());

		}

        if ($publication instanceof ViewableInterface) {

            // Delete views
            $viewableUtils = $this->container->get(ViewableUtils::NAME);
            $viewableUtils->deleteViews($publication);

        }

    }

	public function onPublicationLocked(PublicationEvent $event) {
	}

	public function onPublicationUnlocked(PublicationEvent $event) {
	}

	public function onPublicationPublished(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($publication);

			// Linked entities update
			if ($publication instanceof LinkedToInterface) {
				foreach ($publication->getLinkedEntities() as $entity) {
					if ($entity instanceof IndexableInterface && $entity->isIndexable()) {

						// Search index update
						$searchUtils->replaceEntityInIndex($entity);

					}
				}
			}

		}

		if ($publication instanceof CommentableInterface) {

			// Increment users counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($publication);

		}

		if ($publication instanceof LikableInterface) {

			// Increment users counters
			$likableUtils = $this->container->get(LikableUtils::NAME);
			$likableUtils->incrementUsersLikeCount($publication);

		}

		if ($publication instanceof FeedbackableInterface) {

			// Increment users counters
			$feedbackableUtils = $this->container->get(FeedbackableUtils::NAME);
			$feedbackableUtils->incrementUsersFeedbackCount($publication, 1);

		}

		if ($publication->getNotificationStrategy() != PublicationInterface::NOTIFICATION_STRATEGY_NONE
			&& $publication instanceof AuthoredInterface
			&& !($publication instanceof RepublishableInterface && $publication->getPublishCount() > 0)) {

			// Get session user as "publisher"
			$globalUtils = $this->container->get(GlobalUtils::NAME);
			$user = $globalUtils->getUser();

			// Create activity
			$activityUtils = $this->container->get(ActivityUtils::NAME);
			$activityUtils->createPublishActivity($publication->getUser(), $publication->getType(), $publication->getId(), $user);

		}

		// Resolve main picture to avoid image url redirection
		$this->_resolvePicturesPageImageFilter($publication);

		// Scrape Open Graph URL
		$this->_scrapeOpenGraph($publication);

	}

	public function onPublicationUnpublished(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof IndexableInterface && $publication->isIndexable()) {

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($publication);

			// Linked entities update
			if ($publication instanceof LinkedToInterface) {
				foreach ($publication->getLinkedEntities() as $entity) {
					if ($entity instanceof IndexableInterface && $entity->isIndexable()) {

						// Search index update
						$searchUtils->replaceEntityInIndex($entity);

					}
				}
			}

		}

		if ($publication instanceof ViewableInterface) {

			// Delete listed views
			$viewableUtils = $this->container->get(ViewableUtils::NAME);
			$viewableUtils->deleteViews($publication);

		}

		if ($publication instanceof CommentableInterface) {

			// Decrement users counters
			$commentableUtils = $this->container->get(CommentableUtils::NAME);
			$commentableUtils->incrementUsersCommentCount($publication, -1);

		}

		if ($publication instanceof LikableInterface) {

			// Decrement users counters
			$likableUtils = $this->container->get(LikableUtils::NAME);
			$likableUtils->incrementUsersLikeCount($publication, -1);

		}

		if ($publication instanceof FeedbackableInterface) {

			// Decrement users counters
			$feedbackableUtils = $this->container->get(FeedbackableUtils::NAME);
			$feedbackableUtils->incrementUsersFeedbackCount($publication, -1);

		}

		if ($publication->getNotificationStrategy() != PublicationInterface::NOTIFICATION_STRATEGY_NONE
			&& !($publication instanceof RepublishableInterface)) {

			// Delete activity
			$activityUtils = $this->container->get(ActivityUtils::NAME);
			$activityUtils->deleteActivitiesByEntityTypeAndEntityId($publication->getType(), $publication->getId());

		}

	}

	public function onPublicationShown(PublicationEvent $event) {
		$publication = $event->getPublication();

		if ($publication instanceof ViewableInterface) {

			// Process showed view
			$viewableUtils = $this->container->get(ViewableUtils::NAME);
			$viewableUtils->processShownView($publication);

		}

	}

	public function onPublicationsListed(PublicationsEvent $event) {
		$publications = $event->getPublications();

		$globalUtils = $this->container->get(GlobalUtils::NAME);
		$user = $globalUtils->getUser();
		if (!is_null($user)) {

			// Prepare publication isShown field
			$entityType = null;
			$entityIds = array();
			foreach($publications as $publication) {
				if ($publication instanceof ViewableInterface) {
					$entityType = $publication->getType();
					$entityIds[] = $publication->getId();
					$publication->setIsShown(false);
				}
			}

			if (is_null($entityType)) {
				return;
			}

			$om = $this->container->get('doctrine')->getManager();
			$viewRepository = $om->getRepository(View::CLASS_NAME);
			$views = $viewRepository->findByEntityTypeAndEntityIdsAndUserAndKind($entityType, $entityIds, $user, View::KIND_SHOWN);
			if (!is_null($views) && count($views) > 0) {
				foreach ($publications as $publication) {
					foreach ($views as $view) {
						if ($publication->getId() == $view->getEntityId()) {
							$publication->setIsShown(true);
							break;
						}
					}
				}
			}

			if ($event->isNeedCounterResetRefreshTime()) {

				// Compute unlisted counter for viewable entity type (if outdated) to be able to keep counter value after first list display
				$userUtils = $this->container->get(UserUtils::NAME);
				$userUtils->computeUnlistedCounterByEntityType($user, $entityType, true, false);

			}

			// Process listed view
			$viewableUtils = $this->container->get(ViewableUtils::NAME);
			$viewableUtils->processListedView($publications);

			if ($event->isNeedCounterResetRefreshTime()) {

				// Reset unlisted counter refresh time
				$userUtils->resetUnlistedCounterRefreshTimeByEntityType($entityType);

			}

		}

	}

}