<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_vote")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\VoteRepository")
 */
class Vote extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Vote';
	const STRIPPED_NAME = 'vote';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Vote")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $vote;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Vote /////

	public function getVote() {
		return $this->vote;
	}

	public function setVote(\Ladb\CoreBundle\Entity\Core\Vote $vote) {
		$this->vote = $vote;
		return $this;
	}

}