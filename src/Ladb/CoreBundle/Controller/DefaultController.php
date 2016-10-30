<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller {

	/**
	 * @Route("/", name="core_welcome")
	 * @Template()
	 */
	public function welcomeAction() {
		$response = $this->forward('LadbCoreBundle:Creation:list', array(
            'homepage'  => true,
        ));
		return $response;
	}

	/**
	 * @Route("/connexion", name="core_smartlogin")
	 * @Template()
	 */
	public function smartLoginAction(Request $request) {
		$request->getSession()->set('_security.main.target_path', $request->headers->get('referer'));
		return $this->redirect($this->generateUrl('fos_user_security_login'));
	}

}
