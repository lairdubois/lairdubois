<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_mention")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\MentionRepository")
 */
class Mention extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Mention';
	const STRIPPED_NAME = 'mention';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Mention")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $mention;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Mention /////

	public function setMention(\Ladb\CoreBundle\Entity\Core\Mention $mention) {
		$this->mention = $mention;
		return $this;
	}

	public function getMention() {
		return $this->mention;
	}

}