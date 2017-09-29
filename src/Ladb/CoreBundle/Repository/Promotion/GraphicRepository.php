<?php

namespace Ladb\CoreBundle\Repository\Blog;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class GraphicRepository extends AbstractEntityRepository {

	public function createIsNotDraftQueryBuilder() {
		return $this->createQueryBuilder('a')->where('a.isDraft = false');	// FOSElasticaBundle bug -> use 'a'
	}

}