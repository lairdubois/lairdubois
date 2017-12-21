<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\PublicationInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractPublication implements PublicationInterface {

	const VISIBILITY_PRIVATE = 0;
	const VISIBILITY_PROTECTED = 1;
	const VISIBILITY_PUBLIC = 2;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(name="changed_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $changedAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime", nullable=true)
	 */
	protected $updatedAt;

	/**
	 * @ORM\Column(name="visibility", type="integer")
	 */
	protected $visibility = self::VISIBILITY_PRIVATE;

	/**
	 * @ORM\Column(name="is_draft", type="boolean")
	 */
	protected $isDraft = true;

	/**
	 * @ORM\Column(name="is_locked", type="boolean")
	 */
	protected $isLocked = false;

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

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	// UpdatedAge /////

	public function getUpdatedAge() {
		return $this->getUpdatedAt()->diff(new \DateTime());
	}

	// ChangedAt /////

	public function setChangedAt($changedAt) {
		$this->changedAt = $changedAt;
		return $this;
	}

	public function getChangedAt() {
		return $this->changedAt;
	}

	// Visibility /////

	public function setVisibility($visibility) {
		$this->visibility = $visibility;
		return $this;
	}

	public function getVisibility() {
		return $this->visibility;
	}

	public function getIsPrivate() {
		return $this->getVisibility() == AbstractPublication::VISIBILITY_PRIVATE;
	}

	public function getIsProtected() {
		return $this->getVisibility() == AbstractPublication::VISIBILITY_PROTECTED;
	}

	public function getIsPublic() {
		return $this->getVisibility() == AbstractPublication::VISIBILITY_PUBLIC;
	}

	// IsDraft /////

	public function setIsDraft($isDraft) {
		$this->isDraft = $isDraft;
	}

	public function getIsDraft() {
		return $this->isDraft;
	}

	// IsLocked /////

	public function setIsLocked($isLocked) {
		$this->isLocked = $isLocked;
	}

	public function getIsLocked() {
		return $this->isLocked;
	}

	// NotificationStrategy /////

	public function getNotificationStrategy() {
		return self::NOTIFICATION_STRATEGY_NONE;
	}

	// SubPublications /////

	public function getSubPublications() {
		return null;
	}

}