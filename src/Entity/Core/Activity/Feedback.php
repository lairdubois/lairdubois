<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_feedback")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\FeedbackRepository")
 */
class Feedback extends AbstractActivity {

	const STRIPPED_NAME = 'feedback';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Feedback")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $feedback;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Feedback /////

	public function setFeedback(\App\Entity\Core\Feedback $feedback) {
		$this->feedback = $feedback;
		return $this;
	}

	public function getFeedback() {
		return $this->feedback;
	}

}