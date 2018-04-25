<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;

class KnowledgeUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.knowledge_utils';

	/////

	public function reorderKnowledgeFieldValues(AbstractKnowledge $knowledge, $field) {
		$propertyUtils = $this->get(PropertyUtils::NAME);
		$fieldDef = $knowledge->getFieldDefs()[$field];
		if (!is_null($fieldDef)) {
			$values = $propertyUtils->getValue($knowledge, $field.'_values');
			$iterator = $values->getIterator();
			$iterator->uasort(function ($a, $b) {
				if ($a->getVoteScore() == $b->getVoteScore()) {
					if ($a->getCreatedAt() == $b->getCreatedAt()) {
						return 0;
					}
					return ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1;	// Date DESC
				}
				return ($a->getVoteScore() > $b->getVoteScore()) ? -1 : 1;	// VoteScore DESC
			});
			$propertyUtils->setValue($knowledge, $field.'_values', new \Doctrine\Common\Collections\ArrayCollection(iterator_to_array($iterator)));
		}
	}

	/////

	private function _getValueData(BaseValue $value, $choices, $useChoicesValue) {
		return $useChoicesValue && isset($choices[$value->getData()]) ? $choices[$value->getData()] : $value->getData();
	}

	public function updateKnowledgeField(AbstractKnowledge $knowledge, $field) {
		$propertyUtils = $this->get(PropertyUtils::NAME);
		$fieldDef = $knowledge->getFieldDefs()[$field];
		if (!is_null($fieldDef)) {
			$this->reorderKnowledgeFieldValues($knowledge, $field);
			$values = $propertyUtils->getValue($knowledge, $field.'_values');
			if (!$values->isEmpty()) {

				$mandatory = isset($fieldDef[AbstractKnowledge::ATTRIB_MANDATORY]) && $fieldDef[AbstractKnowledge::ATTRIB_MANDATORY];
				$choices = isset($fieldDef[AbstractKnowledge::ATTRIB_CHOICES]) ? $fieldDef[AbstractKnowledge::ATTRIB_CHOICES] : array();
				$useChoicesValue = isset($fieldDef[AbstractKnowledge::ATTRIB_USE_CHOICES_VALUE]) && $fieldDef[AbstractKnowledge::ATTRIB_USE_CHOICES_VALUE];

				if ($fieldDef[AbstractKnowledge::ATTRIB_MULTIPLE]) {

					$validValuesData = array();
					foreach ($values as $value) {
						if ($value->getVoteScore() >= 0) {
							$validValuesData[] = $this->_getValueData($value, $choices, $useChoicesValue);
						} else {
							break;
						}
					}
					$rejectedValue = count($validValuesData) == 0;

					if ($mandatory && $rejectedValue) {
						$text = $this->_getValueData($values->first(), $choices, $useChoicesValue);
					} else {
						$text = implode(',', $validValuesData);
					}

					$noValue = $rejectedValue && !$mandatory;
					$propertyUtils->setValue($knowledge, $field, $noValue ? null : $text);

				} else {
					$value = $values->first();
					$rejectedValue = $value->getVoteScore() < 0;

					$noValue = $rejectedValue && !$mandatory;
					$propertyUtils->setValue($knowledge, $field, $noValue ? null : $this->_getValueData($value, $choices, $useChoicesValue));
					if (isset($fieldDef[AbstractKnowledge::ATTRIB_LINKED_FIELDS])) {
						foreach ($fieldDef[AbstractKnowledge::ATTRIB_LINKED_FIELDS] as $linkedField) {
							$linkedFieldValue = $noValue ? null : $propertyUtils->getValue($value, $linkedField);
							$propertyUtils->setValue($knowledge, $linkedField, $linkedFieldValue);
						}
					}

				}

				if ($mandatory) {
					$propertyUtils->setValue($knowledge, $field.'_rejected', $rejectedValue);
				}

			} else {
				$propertyUtils->setValue($knowledge, $field, null);
				if (isset($fieldDef[AbstractKnowledge::ATTRIB_LINKED_FIELDS])) {
					foreach ($fieldDef[AbstractKnowledge::ATTRIB_LINKED_FIELDS] as $linkedField) {
						$propertyUtils->setValue($knowledge, $linkedField, null);
					}
				}
			}
		}
	}

	/////

	const SOURCES_HISTORY_KEY = '_ladb_knowledge_value_sources_history';

	public function pushToSourcesHistory(BaseValue $value) {
		$globalUtils = $this->get(GlobalUtils::NAME);

		// Extract sources history from user session
		$session = $globalUtils->getSession();
		$history = $session->get(self::SOURCES_HISTORY_KEY);

		if (is_null($history)) {
			$history = array();
		}

		$sourceInfo = array( $value->getSourceType(), $value->getSource() );
		for ($i = count($history) - 1; $i >= 0; $i--) {
			if (!count($history[$i]) == 2) {
				unset($history[$i]);	// Remove sourceInfo if it is malformated
				continue;
			}
			if ($history[$i][0] == $sourceInfo[0] && $history[$i][1] == $sourceInfo[1]) {
				unset($history[$i]);	// Remove sourceInfo if it exists
				break;
			}
		}
		array_unshift($history, $sourceInfo);			// Add sourceInfo as firts array element
		$history = array_slice($history, 0, 10);	// Maximize history to 10 elements

		// Update sources history to user session
		$session->set(self::SOURCES_HISTORY_KEY, $history);
	}

	public function getValueSourcesHistory() {
		$globalUtils = $this->get(GlobalUtils::NAME);

		// Extract sources history from user session
		$session = $globalUtils->getSession();
		$history = $session->get(self::SOURCES_HISTORY_KEY);

		return $history;
	}

}