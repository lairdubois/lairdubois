<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_mention")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\MentionRepository")
 */
class Mention extends AbstractActivity {

	const STRIPPED_NAME = 'mention';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\Mention")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $mention;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Mention /////

	public function setMention(\App\Entity\Core\Mention $mention) {
		$this->mention = $mention;
		return $this;
	}

	public function getMention() {
		return $this->mention;
	}

}