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

				$accessToken = $this->getParameter('facebook_access_token');

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://graph.facebook.com/v2.12/?id=".$url.'&fields=og_object{engagement}&access_token='.$accessToken);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				$curlResults = curl_exec($curl);
				curl_close($curl);
				$json = json_decode($curlResults, true);
				if (isset($json['og_object']['engagement']['count'])) {
					$count = intval($json['og_object']['engagement']['count']);
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
