<?php

namespace Ladb\CoreBundle\Event;

use Ladb\CoreBundle\Manager\Qa\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;
use Ladb\CoreBundle\Utils\TextureUtils;

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

		if ($votableParent instanceof \Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge) {

			// Update field
			$knowledgeUtils = $this->container->get(KnowledgeUtils::NAME);
			$knowledgeUtils->updateKnowledgeField($votableParent, $votable->getParentEntityField());

			if ($votableParent instanceof \Ladb\CoreBundle\Entity\Knowledge\Wood
				&& ($votable->getParentEntityField() == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_GRAIN || $votable->getParentEntityField() == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_ENDGRAIN)
				&& $votable instanceof BaseValue
			) {

				$textureUtils = $this->container->get(TextureUtils::NAME);
				if ($votable->getVoteScore() < 0) {

					// Delete texture if it exists
					$textureUtils->deleteTexture($votableParent, $votable, false);

				} else {

					// Create texture if it doesn't exist
					$textureUtils->createTexture($votableParent, $votable, false);

				}

			}

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($votableParent);

		} else if ($votableParent instanceof \Ladb\CoreBundle\Entity\Qa\Question) {

			// Compute answer counters
			$questionManager = $this->container->get(QuestionManager::NAME);
			$questionManager->computeAnswerCounters($votableParent);

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($votableParent);

		}

	}

}