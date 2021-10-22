<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use FOS\UserBundle\Model\UserManagerInterface;

class UsersToUsernamesTransformer implements DataTransformerInterface {

	private $userManager;

	public function __construct(UserManagerInterface $userManager) {
		$this->userManager = $userManager;
	}

	/**
	 * Transforms an object (user) to a string (username).
	 */
	public function transform($users) {
		if (null === $users) {
			return '';
		}

		if (!$users instanceof \Doctrine\Common\Collections\Collection) {
			throw new UnexpectedTypeException($users, '\Doctrine\Common\Collections\Collection');
		}

		$usernamesArray = array();
		foreach ($users as $user) {
			$usernamesArray[] = $user->getUsername();
		}
		return implode(',', $usernamesArray);
	}

	/**
	 * Transforms a string (username) to an object (user).
	 */
	public function reverseTransform($usernamesString) {
		if (!$usernamesString) {
			return array();
		}
		$usernamesString = htmlspecialchars_decode($usernamesString, ENT_QUOTES);

		$users = array();
		$usernamesArray = preg_split("/[,;]+/", $usernamesString);
		foreach ($usernamesArray as $username) {
			if (!preg_match("/^[ a-zA-Z0-9]{2,}$/", $username)) {
				continue;
			}
			$user = $this->userManager->findUserByUsername($username);
			if (is_null($user) || in_array($user, $users)) {
				continue;
			}
			$users[] = $user;
		}

		return $users;
	}

}