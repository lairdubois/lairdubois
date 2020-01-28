<?php

namespace Ladb\CoreBundle\Utils;

class OpenGraphUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.open_graph_utils';

	/////

	public function scrape($openGraphId) {

		$appId = $this->getParameter('facebook_app_id');
		$appSecret = $this->getParameter('facebook_app_secret');
		$accessToken = $this->getParameter('facebook_access_token');

		try {

			// Setup Facebook SDK
			$fb = new \Facebook\Facebook([
				'app_id' => $appId,
				'app_secret' => $appSecret,
				'default_graph_version' => 'v3.2',
				'default_access_token' => $accessToken,
			]);

			$request = $fb->request(
				'POST',
				'/',
				array(
					'scrape' => 'true',
					'id' => $openGraphId
				)
			);

			$fb->getClient()->sendRequest($request);

		} catch (\Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			$this->get('logger')->addError('OpenGraphUtils failed to scrape. Facebook SDK returned an error: '.$e->getMessage());
		}

	}

	public function fetchMetas($uri) {
		$curl = curl_init($uri);

		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

		$response = curl_exec($curl);

		curl_close($curl);

		if (!empty($response)) {

			libxml_use_internal_errors(true);
			$doc = new \DOMDocument();
			$doc->loadHTML($response);
			$xpath = new \DOMXPath($doc);
			$query = '//*/meta[starts-with(@property, \'og:\')]';
			$metas = $xpath->query($query);
			$rmetas = array();
			foreach ($metas as $meta) {
				$property = $meta->getAttribute('property');
				$content = $meta->getAttribute('content');
				$rmetas[$property] = $content;
			}
			return $rmetas;

		} else {
			return false;
		}
	}

	public function fetchMetaValue($uri, $property) {
		$metas = $this->fetchMetas($uri);
		if ($metas && isset($metas[$property])) {
			return $metas[$property];
		}
		return false;
	}

}