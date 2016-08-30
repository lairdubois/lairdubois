<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Handler\ResourceUploadHandler;

/**
 * @Route("/resources")
 */
class ResourceController extends Controller {

	/**
	 * @Route("/upload", name="core_resource_upload")
	 * @Template()
	 */
	public function uploadAction() {
		$uploadHandler = $this->get(ResourceUploadHandler::NAME);
		$uploadHandler->handle();
		exit(0);
	}

}
