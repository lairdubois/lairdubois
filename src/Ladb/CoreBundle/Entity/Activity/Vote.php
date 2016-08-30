<?php

namespace Ladb\CoreBundle\Entity\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_vote")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Activity\VoteRepository")
 */
class Vote extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Activity\Vote';
	const STRIPPED_NAME = 'vote';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Vote")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $vote;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Vote /////

	public function setVote(\Ladb\CoreBundle\Entity\Vote $vote) {
		$this->vote = $vote;
		return $this;
	}

	public function getVote() {
		return $this->vote;
	}

}