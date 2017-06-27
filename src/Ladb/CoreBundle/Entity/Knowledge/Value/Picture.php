<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_picture")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Value\PictureRepository")
 */
class Picture extends BaseValue {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Value\Picture';
	const TYPE = 12;

	const TYPE_STRIPPED_NAME = 'picture';

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="picture_id", nullable=false)
	 * @Assert\NotNull
	 * @Assert\Type(type="Ladb\CoreBundle\Entity\Core\Picture")
	 */
	protected $data;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// MainPicture /////

	public function getMainPicture() {
		return $this->getData();
	}

}