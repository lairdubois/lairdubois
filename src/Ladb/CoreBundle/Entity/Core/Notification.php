<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_notification", indexes={
 *     @ORM\Index(name="IDX_NOTIFICATION_ENTITY", columns={"group_identifier"})
 * })
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\NotificationRepository")
 * @ORM\HasLifecycleCallbacks()
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
	 * @ORM\Column(type="string", length=30, name="group_identifier", nullable=true)
	 */
	private $groupIdentifier;

	/**
	 * @ORM\Column(type="boolean", name="is_pending_email")
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

	/**
	 * @ORM\Column(type="boolean", name="is_children_shown")
	 */
	private $isChildrenShown = false;

	/**
	 * @ORM\Column(type="boolean", name="is_folder")
	 */
	private $isFolder = false;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Notification", inversedBy="children")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $folder;

	/**
	 * @ORM\OneToMany(targetEntity="Ladb\CoreBundle\Entity\Core\Notification", mappedBy="folder")
	 * @ORM\OrderBy({"id" = "DESC"})
	 */
	private $children;

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

	// GroupIdentifier /////

	public function setGroupIdentifier($groupIdentifier) {
		$this->groupIdentifier = $groupIdentifier;
		return $this;
	}

	public function getGroupIdentifier() {
		return $this->groupIdentifier;
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

	// IsChildrenShown /////

	public function setIsChildrenShown($isChildrenShown) {
		$this->isChildrenShown = $isChildrenShown;
		return $this;
	}

	public function getIsChildrenShown() {
		return $this->isChildrenShown;
	}

	// IsFolder /////

	public function setIsFolder($isFolder) {
		$this->isFolder = $isFolder;
		return $this;
	}

	public function getIsFolder() {
		return $this->isFolder;
	}

	// Folder /////

	public function setFolder($folder = null) {
		$this->folder = $folder;
		return $this;
	}

	public function getFolder() {
		return $this->folder;
	}

	// Children /////

	public function getChildren() {
		return $this->children;
	}

	/////

	public function getOtherChildrenActivityUsers() {
		$users = array();
		foreach ($this->getChildren() as $child) {
			$user = $child->getActivity()->getUser();
			if ($user == $this->getActivity()->getUser()) {
				continue;	// Exclude folder activity user
			}
			$users[] = $user;
		}
		return array_unique($users);
	}

	/**
	 * @ORM\PreRemove
	 */
	public function preRemove() {
		if ($this->getIsFolder()) {
			$children = $this->getChildren();
			if (count($children) == 1) {

				$children[0]->setIsFolder(false);
				$children[0]->setFolder(null);

			} else if (count($children) > 1) {

				$folder = $children[0];
				$folder->setIsFolder(true);
				$folder->setFolder(null);

				foreach ($children as $child) {
					if ($child == $folder) {
						continue;
					}
					$child->setIsFolder(false);
					$child->setFolder($folder);
				}

			}
		}
	}

}