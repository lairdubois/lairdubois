<?php

namespace App\Utils;

use App\Entity\Core\Review;
use App\Model\DraftableInterface;
use App\Model\HiddableInterface;
use App\Model\ReviewableInterface;

class ReviewableUtils extends AbstractContainerAwareUtils {

	public function deleteReviews(ReviewableInterface $reviewable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);

		$reviews = $reviewRepository->findByEntityTypeAndEntityId($reviewable->getType(), $reviewable->getId());
		foreach ($reviews as $review) {
			$this->deleteReview($review, $reviewable, $activityUtils, $om, false);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteReview(Review $review, ReviewableInterface $reviewable, ActivityUtils $activityUtils, $om, $flush = false) {

		// Update user review count
		if (!($reviewable instanceof DraftableInterface) || ($reviewable instanceof DraftableInterface && !$reviewable->getIsDraft())) {
			$review->getUser()->getMeta()->incrementReviewCount(-1);
		}

		// Update reviewable review count
		$reviewable->incrementReviewCount(-1);

		// Delete relative activities
		$activityUtils->deleteActivitiesByReview($review);

		// Remove Review from DB
		$om->remove($review);

		if ($flush) {
			$om->flush();
		}

	}

	/////

	public function computeAverageRating(ReviewableInterface $reviewable) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);

		$ratingSum = 0;
		$reviewCount = 0;

		// Retrieve reviews
		$reviews = $reviewRepository->findByEntityTypeAndEntityId($reviewable->getType(), $reviewable->getId());

		foreach ($reviews as $review) {
			if ($review->getRating() > 0) {
				$ratingSum += $review->getRating();
				$reviewCount++;
			}
		}
		$reviewable->setAverageRating($reviewCount > 0 ? $ratingSum / $reviewCount : 0);
	}

	/////

	public function getReviewContext(ReviewableInterface $reviewable) {
		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$globalUtils = $this->get(GlobalUtils::class);

		// Retrieve reviews
		$reviews = $reviewRepository->findByEntityTypeAndEntityId($reviewable->getType(), $reviewable->getId());

		// Retrieve current user review
		$userReview = null;
		$user = $globalUtils->getUser();
		if (!is_null($user)) {

			foreach ($reviews as $review) {
				if ($review->getUser() == $user) {
					$userReview = $review;
					break;
				}
			}

		}

		return array(
			'entityType'   => $reviewable->getType(),
			'entityId'     => $reviewable->getId(),
			'reviewCount'  => $reviewable->getReviewCount(),
			'reviews'      => $reviews,
			'userReview'   => $userReview,
			'isReviewable' => $reviewable instanceof HiddableInterface ? $reviewable->getIsPublic() : true,
		);
	}

}