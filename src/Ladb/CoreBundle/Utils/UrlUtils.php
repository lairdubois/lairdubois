<?php

namespace Ladb\CoreBundle\Utils;

class UrlUtils {

	const NAME = 'ladb_core.url_utils';

	/////

	public function truncateUrl($url, $removeProtocol = true, $lengthL = 14, $lengthR = 15, $separator = '...', $charset = 'UTF-8') {
		if (preg_match('/^(?:https?:|)(?:\/\/)/i', $url)) {
			if ($removeProtocol) {
				$url = preg_replace('/^(?:https?:|)(?:\/\/)(?:www.|)/i', '', $url);
			}
			$valueLength = mb_strlen($url, $charset);
			if ($valueLength > $lengthL + $lengthR) {
				return rtrim(mb_substr($url, 0, $lengthL, $charset)).$separator.ltrim(mb_substr($url, $valueLength - $lengthR, $lengthR, $charset));
			}
		}
		return $url;
	}

}