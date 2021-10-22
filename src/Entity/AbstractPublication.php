<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Model\PublicationInterface;
use App\Model\TimestampableTrait;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractPublication implements PublicationInterface {

	use TimestampableTrait;

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
	 * @ORM\Column(name="is_locked", type="boolean")
	 */
	protected $isLocked = false;

	/////

	// Id /////

	public function getId() {
		return $this->id;
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