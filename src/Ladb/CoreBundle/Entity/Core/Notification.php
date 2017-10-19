<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_notification")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\NotificationRepository")
 */
class Notification {

	const CLASS_NAME = 'LadbCoreBundle:Core\Notification';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Activity\AbstractActivity", inversedBy="notifications")
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

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Activity /////

	public function setActivity(\Ladb\CoreBundle\Entity\Core\Activity\AbstractActivity $activity) {
		$this->activity = $activity;
		return $this;
	}

	public function getActivity() {
		return $this->activity;
	}

	// EmailedAt /////

	public function setIsPendingEmail($isPendingEmail) {
		$this->isPendingEmail = $isPendingEmail;
		return $this;
	}

	public function getIsPendingEmail() {
		return $this->isPendingEmail;
	}

	// IsListed /////

	public function setIsListed($isListed) {
		$this->isListed = $isListed;
		return $this;
	}

	public function getIsListed() {
		return $this->isListed;
	}

	// IsShown /////

	public function setIsShown($isShown) {
		$this->isShown = $isShown;
		return $this;
	}

	public function getIsShown() {
		return $this->isShown;
	}

}