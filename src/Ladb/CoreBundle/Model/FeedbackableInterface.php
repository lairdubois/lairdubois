<?php

namespace Ladb\CoreBundle\Model;

interface FeedbackableInterface extends IdentifiableInterface, TypableInterface, TimestampableInterface, TitledInterface {

	// FeedbackCount /////

	public function incrementFeedbackCount($by = 1);

	public function setFeedbackCount($reviewCount);

	public function getFeedbackCount();

}
