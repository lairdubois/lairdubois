<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_longtext")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\TextRepository")
 */
class Longtext extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Longtext';
	const TYPE = 17;

	const TYPE_STRIPPED_NAME = 'longtext';

	/**
	 * @ORM\Column(type="text")
	 * @Assert\NotBlank
	 * @Assert\Length(max=500)
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