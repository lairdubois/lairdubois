<?php

namespace Ladb\CoreBundle\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_write")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Core\Activity\WriteRepository")
 */
class Write extends AbstractActivity {

	const CLASS_NAME = 'LadbCoreBundle:Core\Activity\Write';
	const STRIPPED_NAME = 'write';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Message\Message")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $message;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Message /////

	public function getMessage() {
		return $this->message;
	}

	public function setMessage(\Ladb\CoreBundle\Entity\Message\Message $message) {
		$this->message = $message;
		return $this;
	}

}