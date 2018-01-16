<?php

namespace LadbCoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Ladb\CoreBundle\Entity\Core\User;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

/**
 * @ORM\Table("tbl_core_user_subscription")
 * @ORM\Entity()
 */
class UserSubscription implements UserSubscriptionInterface {

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="LadbCoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string")
	 */
	private $subscriptionHash;

	/**
	 * @ORM\Column(type="json")
	 */
	private $subscription;

	/////

	public function __construct(User $user, string $subscriptionHash, array $subscription) {
		$this->user = $user;
		$this->subscriptionHash = $subscriptionHash;
		$this->subscription = $subscription;
	}

	// Id /////

	public function getId(): ?int {
		return $this->id;
	}

	// User /////

	public function getUser(): UserInterface {
		return $this->user;
	}

	// SubscriptionHash /////

	public function getSubscriptionHash(): string {
		return $this->subscriptionHash;
	}

	// EndPoint /////

	public function getEndpoint(): string {
		return $this->subscription['endpoint'] ?? null;
	}

	// PublicKey /////

	public function getPublicKey(): string {
		return $this->subscription['keys']['p256dh'] ?? null;
	}

	// AuthToken /////

	public function getAuthToken(): string {
		return $this->subscription['keys']['auth'] ?? null;
	}

}