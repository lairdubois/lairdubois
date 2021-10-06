<?php

namespace App\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_find_content_website")
 * @ORM\Entity
 */
class Website extends Link {

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $host;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $title;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $description;

	/////

	// Host /////

	public function setHost($host) {
		$this->host = $host;
		return $this;
	}

	public function getHost() {
		return $this->host;
	}

	// Title /////

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	public function getTitle() {
		return $this->title;
	}

	// Description /////

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	public function getDescription() {
		return $this->description;
	}

}