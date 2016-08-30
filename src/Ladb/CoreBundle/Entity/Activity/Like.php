<?php

namespace Ladb\CoreBundle\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_like")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Activity\LikeRepository")
 */
class Like extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Activity\Like';
	const STRIPPED_NAME = 'like';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Like")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $like;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Like /////

	public function setLike(\Ladb\CoreBundle\Entity\Like $like) {
		$this->like = $like;
		return $this;
	}

	public function getLike() {
		return $this->like;
	}

}