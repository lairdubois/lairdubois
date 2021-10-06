<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_core_user_witness")
 * @ORM\Entity(repositoryClass="App\Repository\Core\UserWitnessRepository")
 */
class UserWitness {

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
	private $createdAt;

	/**
	 * @ORM\Column(type="string", length=25, unique=true)
	 */
	private $username;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User", cascade={"persist"})
	 * @ORM\JoinColumn(name="user_id", nullable=false)
	 */
	private $user;

	/////

	// Id /////

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

	// Username /////

	public function getUsername() {
		return $this->username;
	}

	public function setUsername($username) {
		$this->username = $username;
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