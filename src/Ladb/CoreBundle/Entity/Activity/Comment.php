<?php

namespace Ladb\CoreBundle\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_comment")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Activity\CommentRepository")
 */
class Comment extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Activity\Comment';
	const STRIPPED_NAME = 'comment';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Comment")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $comment;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Comment /////

	public function setComment(\Ladb\CoreBundle\Entity\Comment $comment) {
		$this->comment = $comment;
		return $this;
	}

	public function getComment() {
		return $this->comment;
	}

}