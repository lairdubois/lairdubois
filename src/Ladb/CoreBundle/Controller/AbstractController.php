<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractController extends Controller {

	const LOCK_TTL_CREATE_ACTION = 3;		// 3 seconds

	protected function createLock($name, $blocking = false, $ttl = 300.0, $autoRelease = true) {

		// Lock / Check resource
		if ($blocking) {
			$store = new \Symfony\Component\Lock\Store\SemaphoreStore();
		} else {
			$memcached = new \Memcached();
			$memcached->addServer($this->getParameter('memcached_host'), $this->getParameter('memcached_port'));
			$store = new \Symfony\Component\Lock\Store\MemcachedStore($memcached);
		}
		$factory = new \Symfony\Component\Lock\Factory($store);
		$resource = $name.'_'.($this->getUser() ? $this->getUser()->getId() : '');
		$lock = $factory->createLock($resource, $ttl, $autoRelease);
		if (!$lock->acquire($blocking)) {
			throw $this->createNotFoundException('Resource locked ('.$resource.').');
		}

		return $lock;
	}

	private function _retrieveUser($username) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {

			// Try to load user witness
			$om = $this->getDoctrine()->getManager();
			$userWitnessRepository = $om->getRepository(UserWitness::class);
			$userWitness = $userWitnessRepository->findOneByUsername($username);
			if (is_null($userWitness) || is_null($userWitness->getUser())) {
				throw $this->createNotFoundException('User not found (username='.$username.')');
			}

			$user = $userWitness->getUser();

		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled (username='.$username.')');
		}

		return $user;
	}

	protected function retrieveAsUser(Request $request) {

		$as = $request->get('as');
		if (!is_null($as)) {
			$asUser = $this->_retrieveUser($as);
			if (!is_null($asUser)) {

				// Only team allowed
				if (!$asUser->getIsTeam()) {
					throw $this->createNotFoundException('As user must be a team.');
				}

				// Only members allowed
				$om = $this->getDoctrine()->getManager();
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamIdAndUser($asUser->getId(), $this->getUser())) {
					throw $this->createNotFoundException('Access denied');
				}

			}
			return $asUser;
		}

		return null;
	}

}