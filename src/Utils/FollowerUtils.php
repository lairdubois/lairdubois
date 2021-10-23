<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Core\Follower;
use App\Model\TypableInterface;
use App\Model\TitledInterface;
use App\Entity\Core\User;

class FollowerUtils extends AbstractContainerAwareUtils {

	public function deleteFollowersByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);

		$followers = $followerRepository->findByUser($user);
		foreach ($followers as $follower) {
			$follower->getUser()->getMeta()->incrementFollowingCount(-1);
			$follower->getFollowingUser()->getMeta()->incrementFollowerCount(-1);
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
		$activityUtils = $this->get(ActivityUtils::class);

		$followers = $followerRepository->findByFollowingUser($user);
		foreach ($followers as $follower) {
			$follower->getUser()->getMeta()->incrementFollowingCount(-1);
			$follower->getFollowingUser()->getMeta()->incrementFollowerCount(-1);
			$activityUtils->deleteActivitiesByFollower($follower);
			$om->remove($follower);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteByFollowingUserAndUser(User $followingUser, User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::class);

		$follower = $followerRepository->findOneByFollowingUserIdAndUser($followingUser->getId(), $user);
		if (!is_null($follower)) {
			$follower->getUser()->getMeta()->incrementFollowingCount(-1);
			$follower->getFollowingUser()->getMeta()->incrementFollowerCount(-1);
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