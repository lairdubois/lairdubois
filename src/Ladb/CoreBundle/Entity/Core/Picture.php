<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_picture")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Picture {

	const CLASS_NAME = 'LadbCoreBundle:Core\Picture';

	const ACCEPTED_FILE_TYPE = '/\.(jpe?g|png)$/i';
	const LOAD_MAX_FILE_SIZE = 8388608; // 8Mo
	const MAX_FILE_SIZE = 8388608; // 8Mo
	const VERSION_IMAGE_SIZE = 1024;

	const QUALITY_LD = 'ld';
	const QUALITY_SD = 'sd';
	const QUALITY_HD = 'hd';

	const QUALITY_LD_MIN_SIZE = 32;
	const QUALITY_SD_MIN_SIZE = 256;
	const QUALITY_HD_MIN_SIZE = 512;

	const POST_PROCESSOR_NONE = 'none';
	const POST_PROCESSOR_SQUARE = 'square';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	private $createdAt;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $legend;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="source_url")
	 * @Assert\Length(max=255)
	 * @Assert\Url()
	 */
	private $sourceUrl;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="master_path")
	 */
	private $masterPath;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true, name="transformed_path")
	 */
	private $transformedPath;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $rotation = 0;

	/**
	 * @ORM\Column(type="integer", name="sort_index")
	 */
	private $sortIndex = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $width = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $height = 0;

	/**
	 * @ORM\Column(type="float", name="height_ratio_100")
	 */
	private $heightRatio100 = 100;

	/**
	 * @ORM\Column(type="integer", name="center_x_100")
	 */
	private $centerX100 = 50;

	/**
	 * @ORM\Column(type="integer", name="center_y_100")
	 */
	private $centerY100 = 50;

	/////

	/**
	 * @ORM\PostRemove()
	 */
	public function removeUpload() {
		if (!$this->isMaster()) {
			if ($filename = $this->getAbsoluteTransformedPath()) {
				try {
					unlink($filename);
				} catch (\Exception $e) {}
			}
		}
		if ($filename = $this->getAbsoluteMasterPath()) {
			try {
				unlink($filename);
			} catch (\Exception $e) {}
		}
	}

	/////

	protected function getUploadDir() {
		return 'uploads';
	}

	protected function getUploadRootDir() {
		// the absolute directory path where uploaded documents should be saved
		return __DIR__.'/../../../../../'.$this->getUploadDir();
	}

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// User /////

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user = null) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Legend /////

	public function setLegend($legend) {
		$this->legend = $legend;
		return $this;
	}

	public function getLegend() {
		return $this->legend;
	}

	// SourceUrl /////

	public function setSourceUrl($sourceUrl) {
		$this->sourceUrl = $sourceUrl;
		return $this;
	}

	public function getSourceUrl() {
		return $this->sourceUrl;
	}

	// MasterPath /////

	public function setMasterPath($masterPath) {
		$this->masterPath = $masterPath;
		return $this;
	}

	public function getMasterPath() {
		return $this->masterPath;
	}

	public function getAbsoluteMasterPath() {
		return null === $this->masterPath ? null : $this->getUploadRootDir().'/'.$this->masterPath;
	}

	public function isMaster() {
		return is_null($this->transformedPath) || $this->transformedPath == $this->masterPath;
	}

	// TransformedPath /////

	public function setTransformedPath($transformedPath) {
		$this->transformedPath = $transformedPath;
		return $this;
	}

	public function getTransformedPath() {
		return $this->transformedPath;
	}

	public function getAbsoluteTransformedPath() {
		return null === $this->transformedPath ? null : $this->getUploadRootDir().'/'.$this->transformedPath;
	}

	// Path /////

	public function getPath() {
		if (null === $this->transformedPath) {
			return $this->getMasterPath();
		}
		return $this->transformedPath;
	}

	public function getAbsolutePath() {
		$path = $this->getPath();
		return null === $path ? null : $this->getUploadRootDir().'/'.$path;
	}

	public function getWebPath() {
		$path = $this->getPath();
		return null === $path ? null : '/'.$path;
	}

	// Rotation /////

	public function setRotation($rotation) {
		$this->rotation = $rotation;
		return $this;
	}

	public function getRotation() {
		return $this->rotation;
	}

	// SortIndex /////

	public function setSortIndex($sortIndex) {
		$this->sortIndex = $sortIndex;
		return $this;
	}

	public function getSortIndex() {
		return $this->sortIndex;
	}

	// Width /////

	public function setWidth($width) {
		$this->width = $width;
		return $this;
	}

	public function getWidth() {
		return $this->width;
	}

	// Height /////

	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}

	public function getHeight() {
		return $this->height;
	}

	// HeightRatio100 /////

	public function setHeightRatio100($heightRatio100) {
		$this->heightRatio100 = $heightRatio100;
		return $this;
	}

	public function getHeightRatio100() {
		return $this->heightRatio100;
	}

	// CenterX100 /////

	public function setCenterX100($centerX100) {
		$this->centerX100 = $centerX100;
		return $this;
	}

	public function getCenterX100() {
		return $this->centerX100;
	}

	// CenterY100 /////

	public function setCenterY100($centerY100) {
		$this->centerY100 = $centerY100;
		return $this;
	}

	public function getCenterY100() {
		return $this->centerY100;
	}

}