<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_comment")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\CommentRepository")
 */
class Comment extends AbstractActivity {

	const CLASS_NAME = 'App\Entity\Core\Activity\Comment';
	const STRIPPED_NAME = 'comment';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Comment")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $comment;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Comment /////

	public function setComment(\App\Entity\Core\Comment $comment) {
		$this->comment = $comment;
		return $this;
	}

	public function getComment() {
		return $this->comment;
	}

}