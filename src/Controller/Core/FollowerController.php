<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Core\Follower;
use App\Entity\Core\User;
use App\Utils\FollowerUtils;
use App\Utils\ActivityUtils;

/**
 * @Route("/followers")
 */
class FollowerController extends AbstractController {

	/**
	 * @Route("/{followingUserId}/create", requirements={"followingUserId" = "\d+"}, name="core_follower_create")
	 * @Template("Core/Follower/create-xhr.html.twig")
	 */
	public function create(Request $request, $followingUserId) {

		$this->createLock('core_follower_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$userRepository = $om->getRepository(User::CLASS_NAME);

		// Check related user

		$followerUser = $this->getUser();
		$followingUser = $userRepository->findOneById($followingUserId);
		if (is_null($followingUser)) {
			throw $this->createNotFoundException('Unable to find Following User entity (id='.$followingUserId.').');
		}
		if (!$followingUser->isEnabled()) {
			throw $this->createNotFoundException('User not enabled');
		}

		if (!$followerRepository->existsByFollowingUserIdAndUser($followingUserId, $this->getUser()) && $followingUserId != $this->getUser()->getId()) {

			$follower = new Follower();
			$follower->setUser($followerUser);
			$follower->setFollowingUser($followingUser);

			$om->persist($follower);

			// Update counters

			$followerUser->getMeta()->incrementFollowingCount();
			$followingUser->getMeta()->incrementFollowerCount();

			// Create activity
			$activityUtils = $this->get(ActivityUtils::class);
			$activityUtils->createFollowActivity($follower, false);

			$om->flush();

		}

		if (!$request->isXmlHttpRequest()) {

			// Return to
			$returnToUrl = $request->get('rtu');
			if (is_null($returnToUrl)) {
				$returnToUrl = $request->headers->get('referer');
			}

			return $this->redirect($returnToUrl);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'followerContext' => $followerUtils->getFollowerContext($followingUser, $followerUser),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_follower_delete")
	 * @Template("Core/Follower/delete-xhr.html.twig")
	 */
	public function delete(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);

		$follower = $followerRepository->findOneById($id);
		if (is_null($follower)) {
			throw $this->createNotFoundException('Unable to find Follower entity (id='.$id.').');
		}
		if ($follower->getUser()->getId() != $this->getUser()->getId()) {
			throw $this->createNotFoundException('Not allowed (core_follower_delete)');
		}

		$followingUser = $follower->getFollowingUser();
		$followerUser = $follower->getUser();

		// Update counters
		$followerUser->getMeta()->incrementFollowingCount(-1);
		$followingUser->getMeta()->incrementFollowerCount(-1);

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::class);
		$activityUtils->deleteActivitiesByFollower($follower, false);

		// Delete follower
		$om->remove($follower);
		$om->flush();

		if (!$request->isXmlHttpRequest()) {

			// Return to (use referer because the use is already logged)
			$returnToUrl = $request->headers->get('referer');

			return $this->redirect($returnToUrl);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'followerContext' => $followerUtils->getFollowerContext($followingUser, $followerUser),
		);
	}

}