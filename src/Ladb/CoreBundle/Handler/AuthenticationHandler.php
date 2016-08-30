<?php

namespace Ladb\CoreBundle\Handler;

use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthenticationHandler implements LogoutSuccessHandlerInterface {

	public function onLogoutSuccess(Request $request) {
		$response = new RedirectResponse($request->headers->get('referer'));
		return $response;
	}

}