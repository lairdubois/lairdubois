<?php

namespace App\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\Request;

class GlobalUtils extends AbstractContainerAwareUtils {

	/**
	 * Returns the security context service.
	 *
	 * @return TokenStorage|null The security context
	 */
	public function getSecurity() {
		if ($this->container->has('security.token_storage')) {
			return $this->container->get('security.token_storage');
		}
	}

	/**
	 * Returns the current user.
	 *
	 * @return mixed
	 *
	 * @see TokenInterface::getUser()
	 */
	public function getUser() {
		if (!$security = $this->getSecurity()) {
			return;
		}

		if (!$token = $security->getToken()) {
			return;
		}

		$user = $token->getUser();
		if (!is_object($user)) {
			return;
		}

		return $user;
	}

	/**
	 * Returns the current request.
	 *
	 * @return Request|null The HTTP request object
	 */
	public function getRequest() {
		if ($this->container->has('request_stack')) {
			return $this->container->get('request_stack')->getCurrentRequest();
		}
	}

	/**
	 * Returns the current session.
	 *
	 * @return Session|null The session
	 */
	public function getSession() {
		if ($request = $this->getRequest()) {
			return $request->getSession();
		}
	}

	/**
	 * Returns the current app environment.
	 *
	 * @return string The current environment string (e.g 'dev')
	 */
	public function getEnvironment() {
		return $this->getParameter('kernel.environment');
	}

	/**
	 * Returns the current app debug mode.
	 *
	 * @return bool    The current debug mode
	 */
	public function getDebug() {
		return (bool)$this->getParameter('kernel.debug');
	}

}
