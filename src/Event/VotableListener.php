<?php

namespace App\Event;

use App\Manager\Qa\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Entity\Knowledge\Value\BaseValue;
use App\Model\IndexableInterface;
use App\Utils\SearchUtils;
use App\Utils\KnowledgeUtils;
use App\Utils\TextureUtils;

class VotableListener implements EventSubscriberInterface {

	const VOTE_UPDATED = 'ladb.votable.vote_updated';

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public static function getSubscribedEvents() {
		return array(
			VotableListener::VOTE_UPDATED   => 'onVoteUpdated',
		);
	}

	/////

	public function onVoteUpdated(VotableEvent $event) {
		$votable = $event->getVotable();
		$votableParent = $event->getVotableParent();

		if ($votableParent instanceof \App\Entity\Knowledge\AbstractKnowledge) {

			// Update field
			$knowledgeUtils = $this->container->get(KnowledgeUtils::class);
			$knowledgeUtils->updateKnowledgeField($votableParent, $votable->getParentEntityField());

			if ($votableParent instanceof \App\Entity\Knowledge\Wood
				&& ($votable->getParentEntityField() == \App\Entity\Knowledge\Wood::FIELD_GRAIN || $votable->getParentEntityField() == \App\Entity\Knowledge\Wood::FIELD_ENDGRAIN)
				&& $votable instanceof BaseValue
			) {

				$textureUtils = $this->container->get(TextureUtils::class);
				if ($votable->getVoteScore() < 0) {

					// Delete texture if it exists
					$textureUtils->deleteTexture($votableParent, $votable, false);

				} else {

					// Create texture if it doesn't exist
					$textureUtils->createTexture($votableParent, $votable, false);

				}

			}

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($votableParent);

		} else if ($votableParent instanceof \App\Entity\Qa\Question) {

			// Compute answer counters
			$questionManager = $this->container->get(QuestionManager::class);
			$questionManager->computeAnswerCounters($votableParent);

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::class);
			$searchUtils->replaceEntityInIndex($votableParent);

		}

	}

}