<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\AbstractKnowledge;
use App\Manager\AbstractPublicationManager;
use App\Utils\ActivityUtils;
use App\Utils\CommentableUtils;
use App\Utils\PropertyUtils;
use App\Utils\ReviewableUtils;
use App\Utils\VotableUtils;

abstract class AbstractKnowledgeManager extends AbstractPublicationManager {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.ActivityUtils::class,
            '?'.PropertyUtils::class,
            '?'.ReviewableUtils::class,
            '?'.VotableUtils::class,
        ));
    }

    /////

	protected function deleteKnowledge(AbstractKnowledge $knowledge, $withWitness = true, $flush = true) {

		// Delete values (votes and proposals count)
		$commentableUtils = $this->get(CommentableUtils::class);
		$votableUtils = $this->get(VotableUtils::class);
		$activityUtils = $this->get(ActivityUtils::class);
		$propertyUtils = $this->get(PropertyUtils::class);
		$fieldDefs = $knowledge->getFieldDefs();
		foreach ($fieldDefs as $field => $fieldDef) {
			$values = $propertyUtils->getValue($knowledge, $field.'_values');
			foreach ($values as $value) {

				// Decrement user proposal count
				$value->getUser()->getMeta()->incrementProposalCount(-1);

				// Delete comments
				$commentableUtils->deleteComments($value, false);

				// Delete votes
				$votableUtils->deleteVotes($value, $knowledge, false);

				// Delete activities
				$activityUtils->deleteActivitiesByValue($value, false);

			}
		}

		parent::deletePublication($knowledge, $withWitness, $flush);
	}

}