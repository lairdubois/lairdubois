<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_review")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\ReviewRepository")
 */
class Review extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Review';
	const STRIPPED_NAME = 'review';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Review")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $review;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Review /////

	public function setReview(\App\Entity\Core\Review $review) {
		$this->review = $review;
		return $this;
	}

	public function getReview() {
		return $this->review;
	}

}