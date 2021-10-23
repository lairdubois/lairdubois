<?php

namespace App\Manager\Knowledge;

use App\Entity\Knowledge\Book;
use App\Utils\ReviewableUtils;

class BookManager extends AbstractKnowledgeManager {

	public function delete(Book $book, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::class);
		$reviewableUtils->deleteReviews($book, false);

		parent::deleteKnowledge($book, $withWitness, $flush);
	}

}