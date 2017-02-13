<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/yt")
 */
class YtController extends Controller {

	/**
	 * @Route("/{embedIdentifier}", name="core_yt_show")
	 * @Template()
	 */
	public function showAction(Request $request, $embedIdentifier) {

		$videoHostingUtils = $this->get(VideoHostingUtils::NAME);
		$data = $videoHostingUtils->getVideoSitemapData(VideoHostingUtils::KIND_YOUTUBE, $embedIdentifier);

		return array(
			'kind' => VideoHostingUtils::KIND_YOUTUBE,
			'embedIdentifier' => $embedIdentifier,
			'data' => $data,
		);
	}

}
