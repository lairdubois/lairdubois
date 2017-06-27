<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Follower;
use Ladb\CoreBundle\Model\TypableInterface;
use Ladb\CoreBundle\Model\TitledInterface;
use Ladb\CoreBundle\Entity\Core\User;

class FollowerUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.follower_utils';

	public function deleteFollowersByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$followers = $followerRepository->findByUser($user);
		foreach ($followers as $follower) {
			$follower->getUser()->incrementFollowingCount(-1);
			$follower->getFollowingUser()->incrementFollowerCount(-1);
			$activityUtils->deleteActivitiesByFollower($follower);
			$om->remove($follower);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteFollowingsByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$followers = $followerRepository->findByFollowingUser($user);
		foreach ($followers as $follower) {
			$follower->getUser()->incrementFollowingCount(-1);
			$follower->getFollowingUser()->incrementFollowerCount(-1);
			$activityUtils->deleteActivitiesByFollower($follower);
			$om->remove($follower);
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function getFollowerContext(User $followingUser, User $followerUser = null) {
		$om = $this->getDoctrine()->getManager();
		if (!is_null($followerUser) && $followingUser->getId() == $followerUser->getId()) {
			return null;
		}

		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$follower = null;
		if (!is_null($followerUser)) {
			$follower = $followerRepository->findOneByFollowingUserIdAndUser($followingUser->getId(), $followerUser);
		}

		return array(
			'followingUser' => $followingUser,
			'id'            => is_null($follower) ? null : $follower->getId(),
		);
	}

}