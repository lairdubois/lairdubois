<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Model\ExplorableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;

class ExplorableUtils {

	const NAME = 'ladb_core.explorable_utils';

	protected $searchUtils;

	public function __construct(SearchUtils $searchUtils) {
		$this->searchUtils = $searchUtils;
	}

	public function getPreviousAndNextPublishedUserExplorables(ExplorableInterface $explorable, AbstractEntityRepository $repository, $publishedExplorableCount) {
		$user = $explorable->getUser();
		$id = $explorable->getId();
		$userExplorables = array();

		$previousUserExplorable = $repository->findOnePreviousByUserAndId($user, $id);
		if (is_null($previousUserExplorable) && $publishedExplorableCount > 2) {
			$previousUserExplorable = $repository->findOneLastByUser($user);
		}
		if (!is_null($previousUserExplorable) ) {
			$userExplorables[] = $previousUserExplorable;
		}
		$nextUserExplorable = $repository->findOneNextByUserAndId($user, $id);
		if (is_null($nextUserExplorable) && $publishedExplorableCount > 2) {
			$nextUserExplorable = $repository->findOneFirstByUser($user);
		}
		if (!is_null($nextUserExplorable)) {
			$userExplorables[] = $nextUserExplorable;
		}

		return $userExplorables;
	}

	public function getSimilarExplorables(ExplorableInterface $explorable, $typeName, $entityClassName, $excludedExplorables = null, $limit = 2, $customFilters = null) {
		$tags = $explorable->getTags();
		if (count($tags) > 0) {

			// Implode tag's names in string
			$tagsString = '';
			foreach ($tags as $tag) {
				$tagsString .= ' '.$tag->getLabel();
			}
			$tagsString = trim($tagsString);

			$excludedIds = array();
			$excludedIds[] = $explorable->getId();
			if (!is_null($excludedExplorables)) {
				foreach ($excludedExplorables as $excludedExplorable) {
					$excludedIds[] = $excludedExplorable->getId();
				}
			}

			$filters = array();
			$similarFilter = new \Elastica\Query\Match('tags.label', $tagsString);
			if ($explorable instanceof HiddableInterface) {

				$filter = (new \Elastica\Query\BoolQuery())
					->addMust($similarFilter)
					->addMust(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC )))
				;
				$filters[] = $filter;

			} else {
				$filters[] = $similarFilter;
			}
			if (!is_null($customFilters) && is_array($customFilters)) {
				$filters = array_merge($filters, $customFilters);
			}
			$sort = null;
			$searchResult = $this->searchUtils->searchEntities($filters, $sort, $typeName, $entityClassName, 0, $limit, $excludedIds);

			$similarExplorables = $searchResult->entities;
			if (!is_null($similarExplorables) && count($similarExplorables) == 0) {
				$similarExplorables = null;
			}

		} else {
			$similarExplorables = null;
		}

		return $similarExplorables;
	}

}
