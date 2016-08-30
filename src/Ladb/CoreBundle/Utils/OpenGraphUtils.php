<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Bridge\Monolog\Logger;

class OpenGraphUtils {

	const NAME = 'ladb_core.open_graph_utils';

	private $logger;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	public function scrape($openGraphId) {

		$url = 'https://graph.facebook.com';
		$fields = array(
			'id'     => urlencode($openGraphId),
			'scrape' => 'true',
		);

		$fieldsString = '';
		foreach($fields as $key => $value) {
			$fieldsString .= $key.'='.$value.'&';
		}
		rtrim($fieldsString, '&');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (curl_exec($ch) === false) {
			$this->logger->addError('OpenGraphUtils failed to scrape (openGraphId='.$openGraphId.', error='.curl_error($ch).')');
		}
		curl_close($ch);

	}

}