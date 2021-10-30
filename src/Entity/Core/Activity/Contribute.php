<?php

namespace App\Entity\Core\Activity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_activity_contribute")
 * @ORM\Entity(repositoryClass="App\Repository\Core\Activity\ContributeRepository")
 */
class Contribute extends AbstractActivity {

	const STRIPPED_NAME = 'contribute';

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Knowledge\Value\BaseValue", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $value;

	/////

	// StrippedName /////

	public function getStrippedName() {
		return self::STRIPPED_NAME;
	}

	// Value /////

	public function setValue(\App\Entity\Knowledge\Value\BaseValue $value) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

}