<?php

namespace App\Entity\Webpush;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;

/**
 * @ORM\Table("tbl_webpush_user_subscription")
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
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", name="subscription_hash")
	 */
	private $subscriptionHash;

	/**
	 * @ORM\Column(type="json")
	 */
	private $subscription;

	/////

	public function __construct(UserInterface $user, string $subscriptionHash, array $subscription) {
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
		return $this->subscription['endpoint'] ?? '';
	}

	// PublicKey /////

	public function getPublicKey(): string {
		return $this->subscription['keys']['p256dh'] ?? '';
	}

	// AuthToken /////

	public function getAuthToken(): string {
		return $this->subscription['keys']['auth'] ?? '';
	}

    public function getContentEncoding(): string
    {
        // Return default value
        return 'aesgcm';
    }
}