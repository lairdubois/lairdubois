<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_answer")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\AnswerRepository")
 */
class Answer extends AbstractActivity {

	const STRIPPED_NAME = 'answer';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Qa\Answer")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $answer;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Answer /////

	public function setAnswer(\App\Entity\Qa\Answer $answer) {
		$this->answer = $answer;
		return $this;
	}

	public function getAnswer() {
		return $this->answer;
	}

}