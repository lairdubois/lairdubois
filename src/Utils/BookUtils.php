<?php

namespace App\Utils;

use App\Entity\Knowledge\Book;

class BookUtils extends AbstractContainerAwareUtils {

	public function computeAverageRating(Book $book) {
		$ratingSum = 0;
		$reviewCount = 0;
		foreach ($book->getReviews() as $review) {
			if ($review->getRating() > 0) {
				$ratingSum += $review->getRating();
				$reviewCount++;
			}
		}
		$book->setAverageRating($reviewCount > 0 ? $ratingSum / $reviewCount : 0);
	}

}