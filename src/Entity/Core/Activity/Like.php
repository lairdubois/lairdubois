<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_like")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\LikeRepository")
 */
class Like extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Like';
	const STRIPPED_NAME = 'like';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Like")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $like;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Like /////

	public function setLike(\App\Entity\Core\Like $like) {
		$this->like = $like;
		return $this;
	}

	public function getLike() {
		return $this->like;
	}

}