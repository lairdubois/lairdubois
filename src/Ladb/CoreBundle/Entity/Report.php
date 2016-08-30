<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_report")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\ReportRepository")
 */
class Report {

	const CLASS_NAME = 'LadbCoreBundle:Report';

	const REASON_NUDITY = 1;
	const REASON_VIOLENCE = 2;
	const REASON_HATEFUL = 3;
	const REASON_COPYRIGHT = 4;
	const REASON_SPAM = 5;
	const REASON_CONTENTNOTFOUND = 6;
	const REASON_CONTENTOUTOFCONTEXT = 7;

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="entity_type", type="smallint", nullable=false)
	 */
	private $entityType;

	/**
	 * @ORM\Column(name="entity_id", type="integer", nullable=false)
	 */
	private $entityId;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $reason;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// EntityType /////

	public function setEntityType($entityType) {
		$this->entityType = $entityType;
	}

	public function getEntityType() {
		return $this->entityType;
	}

	// EntityId /////

	public function setEntityId($entityId) {
		$this->entityId = $entityId;
		return $this;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	// User /////

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Reason /////

	public function setReason($reason) {
		$this->reason = $reason;
	}

	public function getReason() {
		return $this->reason;
	}

}