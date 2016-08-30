<?php

namespace Ladb\CoreBundle\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Activity\ActivityRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="integer")
 * @ORM\DiscriminatorMap({1 = "Comment", 2 = "Follow", 3 = "Like", 4 = "Publish", 5 = "Vote", 6 = "Write", 7 = "Contribute", 8 = "Mention", 9 = "Join"})
 */
abstract class AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Activity\AbstractActivity';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	protected $user;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(name="is_pending_notifications", type="boolean")
	 */
	protected $isPendingNotifications = true;

	/////

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Notification", mappedBy="activity", cascade={"remove"})
	 */
	private $notifications;

	// StrippedName /////

	public abstract function getStrippedName();

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// IsPendingNotifications /////

	public function setIsPendingNotifications($isPendingNotifications) {
		$this->isPendingNotifications = $isPendingNotifications;
		return $this;
	}

	public function getIsPendingNotifications() {
		return $this->isPendingNotifications;
	}


}