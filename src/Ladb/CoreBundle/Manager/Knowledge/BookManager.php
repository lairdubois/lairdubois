<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Manager\Knowledge\Book\ReviewManager;

class BookManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.book_manager';

	public function delete(Book $book, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewManager = $this->get(ReviewManager::NAME);
		foreach ($book->getReviews() as $review) {
			$reviewManager->delete($review);
		}

		parent::deleteKnowledge($book, $withWitness, $flush);
	}

}