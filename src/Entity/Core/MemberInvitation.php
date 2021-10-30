<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Model\IdentifiableInterface;

/**
 * @ORM\Table("tbl_core_member_invitation",
 *		uniqueConstraints={
 *			@ORM\UniqueConstraint(name="ENTITY_MEMBER_INVITATION_UNIQUE", columns={"team_id", "recipient_id"})
 * 		},)
 * @ORM\Entity(repositoryClass="App\Repository\Core\MemberInvitationRepository")
 */
class MemberInvitation implements IdentifiableInterface {

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
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(name="team_id", nullable=false)
	 */
	private $team;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $sender;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $recipient;

	// ID /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// Team /////

	public function getTeam() {
		return $this->team;
	}

	public function setTeam($team) {
		$this->team = $team;
		return $this;
	}

	// Sender /////

	public function getSender() {
		return $this->sender;
	}

	public function setSender($sender) {
		$this->sender = $sender;
		return $this;
	}

	// Recipient /////

	public function getRecipient() {
		return $this->recipient;
	}

	public function setRecipient($recipient) {
		$this->recipient = $recipient;
		return $this;
	}

}