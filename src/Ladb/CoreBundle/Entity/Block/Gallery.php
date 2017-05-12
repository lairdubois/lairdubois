<?php

namespace Ladb\CoreBundle\Entity\Block;

use Doctrine\ORM\Mapping as ORM;
use Ladb\CoreBundle\Model\MultiPicturedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\MultiPicturedInterface;

/**
 * @ORM\Table("tbl_core_block_gallery")
 * @ORM\Entity
 */
class Gallery extends AbstractBlock implements MultiPicturedInterface {

	use MultiPicturedTrait;

	/**
	 * @ORM\ManyToMany(targetEntity="Ladb\CoreBundle\Entity\Picture", cascade={"persist"}, fetch="EAGER")
	 * @ORM\JoinTable(name="tbl_core_block_gallery_picture")
	 * @ORM\OrderBy({"sortIndex" = "ASC"})
	 * @Assert\Count(min=1, max=20)
	 */
	private $pictures;

	/////

	public function __construct() {
		$this->pictures = new \Doctrine\Common\Collections\ArrayCollection();
	}

	// StrippedName /////

	public function getStrippedName() {
		return 'gallery';
	}

}