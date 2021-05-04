<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_decimal")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\DecimalRepository")
 */
class Decimal extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Decimal';
	const TYPE = 27;

	const TYPE_STRIPPED_NAME = 'decimal';

	/**
	 * @ORM\Column(type="decimal", precision=10, scale=3)
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