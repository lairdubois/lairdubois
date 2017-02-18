<?php

namespace Ladb\CoreBundle\Utils;

class OpenGraphUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.open_graph_utils';

	/////

	public function scrape($openGraphId) {

		$appId = $this->getParameter('facebook_app_id');
		$appSecret = $this->getParameter('facebook_app_secret');
		$accessToken = $this->getParameter('facebook_access_token');

		// Setup Facebook SDK
		$fb = new \Facebook\Facebook([
			'app_id' => $appId,
			'app_secret' => $appSecret,
			'default_graph_version' => 'v2.8',
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

		try {
			$fb->getClient()->sendRequest($request);
		} catch (\Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
			$this->get('logger')->addError('OpenGraphUtils failed to scrape. Graph returned an error: '.$e->getMessage());
		} catch (\Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			$this->get('logger')->addError('OpenGraphUtils failed to scrape. Facebook SDK returned an error: '.$e->getMessage());
		}

	}

}