<?php

namespace App\Utils;

class ElasticaQueryUtils {

	public function createShouldMatchPhraseQuery($field, $values) {
		$subQueries = explode(',', $values);
		$filter = new \Elastica\Query\BoolQuery();
		foreach ($subQueries as $subQuery) {
			$filter->addShould(new \Elastica\Query\MatchPhrase($field, $subQuery));
		}
		return $filter;
	}
}