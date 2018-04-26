<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_join")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\JoinRepository")
 */
class Join extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Join';
	const STRIPPED_NAME = 'join';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Join")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $join;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Join /////

	public function setJoin(\Ladb\CoreBundle\Entity\Core\Join $join) {
		$this->join = $join;
		return $this;
	}

	public function getJoin() {
		return $this->join;
	}

}