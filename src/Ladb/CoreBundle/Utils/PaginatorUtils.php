<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginatorUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.paginator_utils';

	/////

	private function _removeEmptyParameters(array $parameters) {
		$fullParameters = array();
		foreach($parameters as $key => $value) {
			if (!empty($value)) {
				$fullParameters[$key] = $value;
			}
		}
		return $fullParameters;
	}

	/////

	public function computePaginatorOffset($page, $countOnFirstPage = 15, $countOnPage = 16) {
		$page = intval($page);
		if ($page < 0) {
			return 0;
		}
		return $page == 0 ? 0 : $countOnFirstPage + ($page - 1) * $countOnPage;
	}

	public function computePaginatorLimit($page, $countOnFirstPage = 15, $countOnPage = 16) {
		$page = intval($page);
		if ($page < 0) {
			return 5000;
		}
		return $page == 0 ? $countOnFirstPage : $countOnPage;
	}

	public function computePaginatorNextPage($page, $totalHits, $countOnFirstPage = 15, $countOnPage = 16) {
		$page = intval($page);
		if ($page < 0) {
			return -1;
		}
		$pageCount = $totalHits > $countOnFirstPage ? 1 + ceil(($totalHits - $countOnFirstPage) / $countOnPage) : 1;
		$nextPage = $page + 1;
		if ($nextPage >= $pageCount) {
			return 0; // No more pages, returns 0
		}
		return $nextPage;
	}

	public function generatePrevAndNextPageUrl($route, $parameters, $page, $totalHits, $countOnFirstPage = 15, $countOnPage = 16 , $referenceType = UrlGeneratorInterface::ABSOLUTE_URL) {
		$pageUrls = new \stdClass();
		if ($page < 0) {
			$pageUrls->prev = null;
			$pageUrls->next = null;
		} else {
			$prevPage = $page - 1;
			$nextPage = $this->computePaginatorNextPage($page, $totalHits, $countOnFirstPage, $countOnPage);
			if ($prevPage >= 0) {
				$pageUrls->prev = $this->get('router')->generate($route, $this->_removeEmptyParameters(array_merge($parameters, array( 'page' => $prevPage ))), $referenceType);
			} else {
				$pageUrls->prev = null;
			}
			if ($nextPage > 0) {
				$pageUrls->next = $this->get('router')->generate($route, $this->_removeEmptyParameters(array_merge($parameters, array( 'page' => $nextPage ))), $referenceType);
			} else {
				$pageUrls->next = null;
			}
		}
		return $pageUrls;
	}

}