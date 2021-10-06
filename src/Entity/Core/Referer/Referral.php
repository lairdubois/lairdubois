<?php

namespace App\Entity\Core\Referer;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_referer_referral")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Referer\ReferralRepository")
 */
class Referral {

	const CLASS_NAME = 'App\Entity\Core\Referer\Referral';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=true)
	 */
	private $entityType;

	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=true)
	 */
	private $entityId;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\Column(name="updated_at", type="datetime", nullable=true)
	 * @Gedmo\Timestampable(on="update")
	 */
	private $updatedAt;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $title;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $url;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Referer\Referer")
	 * @ORM\JoinColumn(name="referer_id", nullable=false)
	 */
	private $referer;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $enabled = false;

	/**
	 * @ORM\Column(name="access_count", type="integer")
	 */
	private $accessCount = 0;

	/////

	private $displayRedirectionWarning = true;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// EntityType /////

	public function getEntityType() {
		return $this->entityType;
	}

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	// EntityId /////

	public function getEntityId() {
		return $this->entityId;
	}

	public function setEntityId($entityId) {
		$this->entityId = $entityId;
		return $this;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// UpdatedAt /////

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
		return $this;
	}

	// Label /////

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($label) {
		$this->title = $label;
		return $this;
	}

	// Url /////

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	// Referer /////

	public function getReferer() {
		return $this->referer;
	}

	public function setReferer($referrer) {
		$this->referer = $referrer;
		return $this;
	}

	// Enabled /////

	public function getEnabled() {
		return $this->enabled;
	}

	public function setEnabled($enabled) {
		$this->enabled = $enabled;
		return $this;
	}

	// AccessCount /////

	public function incrementAccessCount($by = 1) {
		$this->accessCount += intval($by);
	}

	public function getAccessCount() {
		return $this->accessCount;
	}

	// displayRedirectionWarning /////

	public function getDisplayRedirectionWarning() {
		return $this->displayRedirectionWarning;
	}

	public function setDisplayRedirectionWarning($displayRedirectionWarning) {
		$this->displayRedirectionWarning = $displayRedirectionWarning;
		return $this;
	}

}