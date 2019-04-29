<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/api")
 */
class ApiController extends Controller {

	/**
	 * @Route("/{network}/share.count.json", defaults={"_format" = "json"}, name="core_api_network_share_count")
	 * @Template("LadbCoreBundle:Core/Api:networkShareCount.json.twig")
	 */
	public function networkShareCountAction(Request $request, $network) {

		$url = $request->get('url');
		$count = 0;

		if (strpos($url, 'https://www.lairdubois.fr/') != 0) {
			throw $this->createNotFoundException('Invalid URL (url='.$url.')');
		}

		switch ($network) {

			case 'facebook':

				// Facebook

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
						'GET',
						'/',
						array(
							'id' => $url,
							'fields' => 'og_object{engagement}',
						)
					);

					$response = $fb->getClient()->sendRequest($request);
					$decodedBody = $response->getDecodedBody();
					if (isset($decodedBody['og_object']['engagement']['count'])) {
						$count = intval($decodedBody['og_object']['engagement']['count']);
					}

				} catch(\Facebook\Exceptions\FacebookSDKException $e) {
					throw $this->createNotFoundException('Facebook SDK returned an error: '.$e->getMessage());
				}

				break;

			default:
				throw $this->createNotFoundException('Invalid Network (network='.$network.')');

		}

		return array(
			'url' => $url,
			'network' => $network,
			'count' => $count,
		);
	}

}
