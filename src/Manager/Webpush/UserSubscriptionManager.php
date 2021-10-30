<?php

namespace App\Manager\Webpush;

use App\Entity\Webpush\UserSubscription;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserSubscriptionManager implements UserSubscriptionManagerInterface  {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = array()): UserSubscriptionInterface {
		return new UserSubscription($user, $subscriptionHash, $subscription);
	}

	public function hash(string $endpoint, UserInterface $user): string {
		return md5($endpoint); // Encode it as you like
	}

	public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface {
		return $this->om->getRepository(UserSubscription::class)->findOneBy([
			'user' => $user,
			'subscriptionHash' => $subscriptionHash,
		]);
	}

	public function findByUser(UserInterface $user): iterable {
		return $this->om->getRepository(UserSubscription::class)->findBy([
			'user' => $user,
		]);
	}

	public function findByHash(string $subscriptionHash): iterable {
		return $this->om->getRepository(UserSubscription::class)->findBy([
			'subscriptionHash' => $subscriptionHash,
		]);
	}

	public function save(UserSubscriptionInterface $userSubscription): void {
		$this->om->persist($userSubscription);
		$this->om->flush();
	}

	public function delete(UserSubscriptionInterface $userSubscription): void {
		$this->om->remove($userSubscription);
		$this->om->flush();
	}

}