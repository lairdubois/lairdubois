<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_answer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\AnswerRepository")
 */
class Answer extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Answer';
	const STRIPPED_NAME = 'answer';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Qa\Answer")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $answer;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Answer /////

	public function setAnswer(\Ladb\CoreBundle\Entity\Qa\Answer $answer) {
		$this->answer = $answer;
		return $this;
	}

	public function getAnswer() {
		return $this->answer;
	}

}