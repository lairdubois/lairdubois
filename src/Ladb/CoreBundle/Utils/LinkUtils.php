<?php

namespace Ladb\CoreBundle\Utils;

class LinkUtils {

	const NAME = 'ladb_core.link_utils';

	/////

	public function getCanonicalUrl($url) {

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		// Some sites don't like crawlers, so pretend to be a browser
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36'
		]);
		$body = curl_exec($ch);
		$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		if ($finalUrl) {
			$url = $finalUrl;
		}
		// Check for rel=canonical
		if ($body) {

			$dom = new \DOMDocument();
			libxml_use_internal_errors(true); // suppress parse errors and warnings
			// Force interpreting this as UTF-8
			@$dom->loadHTML('<?xml encoding="UTF-8">'.$body, LIBXML_NOWARNING | LIBXML_NOERROR);
			libxml_clear_errors();
			if ($dom) {
				$links = $dom->getElementsByTagName('link');
				foreach ($links as $link) {
					$rels = [];
					if ($link->hasAttribute('rel') && ($relAtt = $link->getAttribute('rel')) !== '') {
						$rels = preg_split('/\s+/', trim($relAtt));
					}
					if (in_array('canonical', $rels)) {
						$url = $link->getAttribute('href');
					}
				}
			}
		}

		return $url;
	}

}

