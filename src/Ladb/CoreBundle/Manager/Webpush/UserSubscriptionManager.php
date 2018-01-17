<?php

namespace Ladb\CoreBundle\Manager\Webpush;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Ladb\CoreBundle\Entity\Webpush\UserSubscription;

class UserSubscriptionManager implements UserSubscriptionManagerInterface  {

	private $doctrine;

	public function __construct(ManagerRegistry $doctrine) {
		$this->doctrine = $doctrine;
	}

	public function factory(UserInterface $user, string $subscriptionHash, array $subscription): UserSubscriptionInterface {
		return new UserSubscription($user, $subscriptionHash, $subscription);
	}

	public function hash(string $endpoint): string {
		return md5($endpoint); // Encode it as you like
	}

	public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface {
		return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findOneBy([
			'user' => $user,
			'subscriptionHash' => $subscriptionHash,
		]);
	}

	public function findByUser(UserInterface $user): iterable {
		return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findBy([
			'user' => $user,
		]);
	}

	public function findByHash(string $subscriptionHash): iterable {
		return $this->doctrine->getManager()->getRepository(UserSubscription::class)->findBy([
			'subscriptionHash' => $subscriptionHash,
		]);
	}

	public function save(UserSubscriptionInterface $userSubscription): void {
		$this->doctrine->getManager()->persist($userSubscription);
		$this->doctrine->getManager()->flush();
	}

	public function delete(UserSubscriptionInterface $userSubscription): void {
		$this->doctrine->getManager()->remove($userSubscription);
		$this->doctrine->getManager()->flush();
	}

}