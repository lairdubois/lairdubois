<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller {

	/**
	 * @Route("/", name="core_welcome")
	 */
	public function welcomeAction() {
		$response = $this->forward('LadbCoreBundle:Wonder/Creation:list', array(
			'homepage' => true,
		));
		return $response;
	}

	/**
	 * @Route("/connexion", name="core_smartlogin")
	 */
	public function smartLoginAction(Request $request) {
		$request->getSession()->set('_security.main.target_path', $request->headers->get('referer'));
		return $this->redirect($this->generateUrl('fos_user_security_login'));
	}

}
