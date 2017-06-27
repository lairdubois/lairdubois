<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_notification")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\NotificationRepository")
 */
class Notification {

	const CLASS_NAME = 'LadbCoreBundle:Core\Notification';
	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;
	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Activity\AbstractActivity", inversedBy="notifications")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $activity;

	/**
	 * @ORM\Column(name="is_pending_email", type="boolean")
	 */
	private $isPendingEmail = true;

	/**
	 * @ORM\Column(type="boolean", name="is_listed")
	 */
	private $isListed = false;

	/**
	 * @ORM\Column(type="boolean", name="is_shown")
	 */
	private $isShown = false;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	// Activity /////

	public function getActivity() {
		return $this->activity;
	}

	public function setActivity(\Ladb\CoreBundle\Entity\Activity\AbstractActivity $activity) {
		$this->activity = $activity;
		return $this;
	}

	// EmailedAt /////

	public function getIsPendingEmail() {
		return $this->isPendingEmail;
	}

	public function setIsPendingEmail($isPendingEmail) {
		$this->isPendingEmail = $isPendingEmail;
		return $this;
	}

	// IsListed /////

	public function getIsListed() {
		return $this->isListed;
	}

	public function setIsListed($isListed) {
		$this->isListed = $isListed;
		return $this;
	}

	// IsShown /////

	public function getIsShown() {
		return $this->isShown;
	}

	public function setIsShown($isShown) {
		$this->isShown = $isShown;
		return $this;
	}

}