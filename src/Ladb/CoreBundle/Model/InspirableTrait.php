<?php

namespace Ladb\CoreBundle\Model;

trait InspirableTrait {

	// ReboundCount /////

	public function incrementReboundCount($by = 1) {
		return $this->reboundCount += intval($by);
	}

	public function getReboundCount() {
		return $this->reboundCount;
	}

	// Rebounds /////

	public function getRebounds() {
		return $this->rebounds;
	}

	// InspirationCount /////

	public function getInspirationCount() {
		return $this->inspirationCount;
	}

	// Inspirations /////

	public function addInspiration(InspirableInterface $inspiration) {
		assert(get_class($inspiration) == get_class($this), 'Inspiration and Rebound need to derive from the same type.');
		if (!$this->inspirations->contains($inspiration)) {
			$this->inspirations[] = $inspiration;
			$this->inspirationCount = count($this->inspirations);
			if (!$this->getIsDraft()) {
				$inspiration->incrementReboundCount();
			}
		}
		return $this;
	}

	public function removeInspiration(InspirableInterface $inspiration) {
		if ($this->inspirations->removeElement($inspiration)) {
			$this->inspirationCount = count($this->inspirations);
			if (!$this->getIsDraft()) {
				$inspiration->incrementReboundCount(-1);
			}
		}
	}

	public function getInspirations() {
		return $this->inspirations;
	}

}