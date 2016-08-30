<?php

namespace Ladb\CoreBundle\Repository\Howto;

use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ArticleRepository extends AbstractEntityRepository {

	/////

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a', 'h', 'bbs', 'ars', 'pls', 'cts', 'wks' ))
			->from($this->getEntityName(), 'a')
			->innerJoin('a.howto', 'h')
			->leftJoin('a.bodyBlocks', 'bbs')
			->leftJoin('h.articles', 'ars')
			->leftJoin('h.plans', 'pls')
			->leftJoin('h.creations', 'cts')
			->leftJoin('h.workshops', 'wks')
			->where('a.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLastPublishedArticleByHowto(Howto $howto) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'a' ))
			->from($this->getEntityName(), 'a')
			->where('a.isDraft = false')
			->andWhere('a.howto = :howto')
			->orderBy('a.createdAt', 'DESC')
			->setParameter('howto', $howto)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

}