<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_core_activity_feedback")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\FeedbackRepository")
 */
class Feedback extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Feedback';
	const STRIPPED_NAME = 'feedback';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Feedback")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $feedback;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Feedback /////

	public function setFeedback(\Ladb\CoreBundle\Entity\Core\Feedback $feedback) {
		$this->feedback = $feedback;
		return $this;
	}

	public function getFeedback() {
		return $this->feedback;
	}

}