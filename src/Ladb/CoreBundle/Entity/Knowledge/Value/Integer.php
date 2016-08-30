<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_integer")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\IntegerRepository")
 */
class Integer extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Integer';
	const TYPE = 11;

	const TYPE_STRIPPED_NAME = 'integer';

	/**
	 * @ORM\Column(type="integer")
	 * @Assert\Type(type="numeric")
	 * @Assert\Range(min=0)
	 * @Assert\NotNull
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

}