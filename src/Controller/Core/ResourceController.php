<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Handler\ResourceUploadHandler;

/**
 * @Route("/resources")
 */
class ResourceController extends AbstractController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.ResourceUploadHandler::class,
        ));
    }

    /**
	 * @Route("/upload", name="core_resource_upload")
	 */
	public function upload() {
		$uploadHandler = $this->get(ResourceUploadHandler::class);
		$uploadHandler->handle();
		exit(0);
	}

}
