<?php

namespace Ladb\CoreBundle\Manager\Knowledge;

use Ladb\CoreBundle\Entity\Knowledge\Book;

class BookManager extends AbstractKnowledgeManager {

	const NAME = 'ladb_core.book_manager';

	public function delete(Book $book, $withWitness = true, $flush = true) {
		parent::deleteKnowledge($book, $withWitness, $flush);
	}

}