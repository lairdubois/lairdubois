<?php

namespace Ladb\CoreBundle\Manager\Knowledge\Book;

use Ladb\CoreBundle\Entity\Knowledge\Book\Review;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Utils\ActivityUtils;

class ReviewManager extends AbstractManager {

	const NAME = 'ladb_core.knowledge_book_review_manager';

	/////

	public function delete(Review $review, $flush = true) {

		$book = $review->getBook();

		// Decrement user review count
		$review->getUser()->getMeta()->incrementReviewCount(-1);

		// Decrement book review count
		$book->incrementReviewCount(-1);

		// Delete activities
//		$activityUtils = $this->get(ActivityUtils::NAME);
//		$activityUtils->deleteActivitiesByReview($review, false);

		parent::deleteEntity($review, $flush);
	}

}