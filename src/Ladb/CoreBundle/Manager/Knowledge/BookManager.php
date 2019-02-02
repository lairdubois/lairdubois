<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Book;
use Ladb\CoreBundle\Manager\Knowledge\Book\ReviewManager;
use Ladb\CoreBundle\Utils\ReviewableUtils;

class BookManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.book_manager';

	public function delete(Book $book, $withWitness = true, $flush = true) {

		// Delete reviews
		$reviewableUtils = $this->get(ReviewableUtils::NAME);
		$reviewableUtils->deleteReviews($book, false);

		parent::deleteKnowledge($book, $withWitness, $flush);
	}

}