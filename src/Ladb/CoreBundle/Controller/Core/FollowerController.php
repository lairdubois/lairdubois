<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Follower;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;

/**
 * @Route("/followers")
 */
class FollowerController extends Controller {

	/**
	 * @Route("/{followingUserId}/create", requirements={"followingUserId" = "\d+"}, name="core_follower_create")
	 * @Template("LadbCoreBundle:Core/Follower:create-xhr.html.twig")
	 */
	public function createAction(Request $request, $followingUserId) {
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
			$activityUtils = $this->get(ActivityUtils::NAME);
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'followerContext' => $followerUtils->getFollowerContext($followingUser, $followerUser),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_follower_delete")
	 * @Template("LadbCoreBundle:Core/Follower:delete-xhr.html.twig")
	 */
	public function deleteAction(Request $request, $id) {
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
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByFollower($follower, false);

		// Delete follower
		$om->remove($follower);
		$om->flush();

		if (!$request->isXmlHttpRequest()) {

			// Return to (use referer because the use is already logged)
			$returnToUrl = $request->headers->get('referer');

			return $this->redirect($returnToUrl);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'followerContext' => $followerUtils->getFollowerContext($followingUser, $followerUser),
		);
	}

}