<?php

namespace Ladb\CoreBundle\Handler;

use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthenticationHandler implements LogoutSuccessHandlerInterface {

	protected $router;

	public function __construct(\Symfony\Bundle\FrameworkBundle\Routing\Router $router) {
		$this->router = $router;
	}

	/////

	public function onLogoutSuccess(Request $request) {
		if ($request->headers->get('referer')) {
			$url = $request->headers->get('referer');
		} else {
			$url = $this->router->generate('core_welcome');
		}
		return new RedirectResponse($url);
	}

}