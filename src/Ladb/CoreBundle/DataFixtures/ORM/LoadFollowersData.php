<?php

namespace Ladb\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Follower;

class LoadFollowersData extends AbstractFixture implements OrderedFixtureInterface {

	public function load(ObjectManager $manager) {

		for ($i = 0; $i < 20; ++$i) {
			$user = $manager->merge($this->getReference('user-1'));
			$followingUser = $manager->merge($this->getReference('user-'.(3 + $i)));

			$follower = new Follower();
			$follower->setUser($user);
			$follower->setFollowingUser($followingUser);
			$manager->persist($follower);

			$user->incrementFollowingCount();
			$followingUser->incrementFollowerCount();
		}

		for ($i = 0; $i < 20; ++$i) {
			$user = $manager->merge($this->getReference('user-'.(25 + $i)));
			$followingUser = $manager->merge($this->getReference('user-2'));

			$follower = new Follower();
			$follower->setUser($user);
			$follower->setFollowingUser($followingUser);
			$manager->persist($follower);

			$user->incrementFollowingCount();
			$followingUser->incrementFollowerCount();
		}

		$manager->flush();
	}

	public function getOrder() {
		return 2;
	}

}
