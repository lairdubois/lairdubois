<?php

namespace Ladb\CoreBundle\Controller\Opencutlist;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Opencutlist\Download;

/**
 * @Route("/opencutlist")
 */
class OpencutlistController extends AbstractController {

	/**
	 * @Route("/", name="core_opencutlist_ew")
	 */
	public function ewAction() {
		$response = $this->redirect('https://extensions.sketchup.com/extension/00f0bf69-7a42-4295-9e1c-226080814e3e/open-cut-list');
		return $response;
	}

	/**
	 * @Route("/download", name="core_opencutlist_download")
	 * @Route("/download/{env}", requirements={"env" = "dev"}, name="core_opencutlist_download_env")
	 */
	public function downloadAction(Request $request, $env = 'prod') {
		$om = $this->getDoctrine()->getManager();

		$download = new Download();
		$download->setEnv($env);
		$download->setClientIp4($request->getClientIp());
		$download->setClientUserAgent($request->server->get('HTTP_USER_AGENT'));

		$om->persist($download);
		$om->flush();

		$response = $this->redirect('https://raw.githubusercontent.com/lairdubois/lairdubois-opencutlist-sketchup-extension/master/dist/ladb_opencutlist'.($env == 'dev' ? '-dev' : '').'.rbz');
		return $response;
	}

}