<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_longtext")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\TextRepository")
 */
class Longtext extends BaseValue {

	const CLASS_NAME = 'App\Entity\Knowledge\Value\Longtext';
	const TYPE = 17;

	const TYPE_STRIPPED_NAME = 'longtext';

	/**
	 * @ORM\Column(type="text")
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=10000)
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Title /////

	public function getTitle() {
		return $this->getData();
	}

}