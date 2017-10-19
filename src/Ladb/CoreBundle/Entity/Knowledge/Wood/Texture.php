<?php

namespace Ladb\CoreBundle\Entity\Knowledge\Wood;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_knowledge2_wood_texture")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Knowledge\Wood\TextureRepository")
 */
class Texture {

	const CLASS_NAME = 'LadbCoreBundle:Knowledge\Wood\Texture';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Wood", inversedBy="textures")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $wood;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Knowledge\Value\Picture")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $value;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="single_picture_id", nullable=false)
	 */
	private $singlePicture;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"persist"})
	 * @ORM\JoinColumn(name="mosaic_picture_id", nullable=false)
	 */
	private $mosaicPicture;

	/**
	 * @ORM\Column(type="integer", name="download_count")
	 */
	private $downloadCount = 0;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// Wood /////

	public function getWood() {
		return $this->wood;
	}

	public function setWood(\Ladb\CoreBundle\Entity\Knowledge\Wood $wood = null) {
		$this->wood = $wood;
		return $this;
	}

	// Value /////

	public function getValue() {
		return $this->value;
	}

	public function setValue(\Ladb\CoreBundle\Entity\Knowledge\Value\Picture $value) {
		$this->value = $value;
		return $this;
	}

	// SinglePicture /////

	public function getSinglePicture() {
		return $this->singlePicture;
	}

	public function setSinglePicture(\Ladb\CoreBundle\Entity\Core\Picture $singlePicture = null) {
		$this->singlePicture = $singlePicture;
		return $this;
	}

	// MosaicPicture /////

	public function getMosaicPicture() {
		return $this->mosaicPicture;
	}

	public function setMosaicPicture(\Ladb\CoreBundle\Entity\Core\Picture $mosaicPicture = null) {
		$this->mosaicPicture = $mosaicPicture;
		return $this;
	}

	// DownloadCount /////

	public function incrementDownloadCount($by = 1) {
		return $this->downloadCount += intval($by);
	}

	public function getDownloadCount() {
		return $this->downloadCount;
	}

	public function setDownloadCount($downloadCount) {
		$this->downloadCount = $downloadCount;
		return $this;
	}

}