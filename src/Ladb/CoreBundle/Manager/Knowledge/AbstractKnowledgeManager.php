<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\ActivityUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;
use Ladb\CoreBundle\Utils\VotableUtils;

abstract class AbstractKnowledgeManager extends AbstractPublicationManager {

	protected function deleteKnowledge(AbstractKnowledge $knowledge, $withWitness = true, $flush = true) {

		// Delete values (votes and proposals count)
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$votableUtils = $this->get(VotableUtils::NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);
		$propertyUtils = $this->get(PropertyUtils::NAME);
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