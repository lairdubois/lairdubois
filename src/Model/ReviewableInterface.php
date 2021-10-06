<?php

namespace App\Model;

interface ReviewableInterface extends IdentifiableInterface, TypableInterface, TimestampableInterface, TitledInterface {

	// ReviewCount /////

	public function incrementReviewCount($by = 1);

	public function setReviewCount($reviewCount);

	public function getReviewCount();

	// AverageRating /////

	public function setAverageRating($averageRating);

	public function getAverageRating();

}
