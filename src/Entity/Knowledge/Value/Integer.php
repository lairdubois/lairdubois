<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_integer")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\IntegerRepository")
 */
class Integer extends BaseValue {

	const TYPE = 11;

	const TYPE_STRIPPED_NAME = 'integer';

	/**
	 * @ORM\Column(type="integer")
	 * @Assert\NotNull(groups={"mandatory"})
	 * @Assert\Type(type="numeric")
	 * @Assert\Range(min=0)
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

}