<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_url")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\UrlRepository")
 */
class Url extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Url';
	const TYPE = 13;

	const TYPE_STRIPPED_NAME = 'url';

	/**
	 * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=255)
	 * @Assert\Url()
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

}