<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\IdentifiableInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_member")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\MemberRepository")
 */
class Member implements IdentifiableInterface {

	const CLASS_NAME = 'LadbCoreBundle:Core\Member';

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
	 * @ORM\Column(name="team_id", type="integer")
	 */
	private $teamId;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(name="team_id", nullable=false)
	 */
	private $team;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $user;

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

	// TeamId /////

	public function getTeamId() {
		return $this->teamId;
	}

	public function setTeamId($teamId) {
		$this->teamId = $teamId;
		return $this;
	}

	// Team /////

	public function getTeam() {
		return $this->team;
	}

	public function setTeam($team) {
		$this->team = $team;
		if (!is_null($team)) {
			$this->teamId = $team->getId();
		}
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

}