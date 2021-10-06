<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_join")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\JoinRepository")
 */
class Join extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Join';
	const STRIPPED_NAME = 'join';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Join")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $join;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Join /////

	public function setJoin(\App\Entity\Core\Join $join) {
		$this->join = $join;
		return $this;
	}

	public function getJoin() {
		return $this->join;
	}

}