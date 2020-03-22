<?php

namespace Ladb\CoreBundle\Model;

trait FeedbackableTrait {

	// FeedbackCount /////

	public function incrementFeedbackCount($by = 1) {
		return $this->feedbackCount += intval($by);
	}

	public function setFeedbackCount($feedbackCount) {
		$this->feedbackCount = $feedbackCount;
		return $this;
	}

	public function getFeedbackCount() {
		return $this->feedbackCount;
	}

}