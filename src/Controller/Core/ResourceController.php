<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Handler\ResourceUploadHandler;

/**
 * @Route("/resources")
 */
class ResourceController extends AbstractController {

	/**
	 * @Route("/upload", name="core_resource_upload")
	 */
	public function upload() {
		$uploadHandler = $this->get(ResourceUploadHandler::class);
		$uploadHandler->handle();
		exit(0);
	}

}
