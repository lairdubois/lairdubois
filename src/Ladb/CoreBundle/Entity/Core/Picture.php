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

	const DEFAULT_ACCEPTED_FILE_TYPE = '/\.(jpe?g|png)$/i';
	const DEFAULT_LOAD_MAX_FILE_SIZE = 8388608; // 8Mo
	const DEFAULT_MAX_FILE_SIZE = 8388608; // 8Mo
	const DEFAULT_IMAGE_MIN_WIDTH = 256;
	const DEFAULT_IMAGE_MIN_HEIGHT = 256;
	const DEFAULT_IMAGE_MAX_WIDTH = 1024;
	const DEFAULT_IMAGE_MAX_HEIGHT = 1024;

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
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $masterPath;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $transformedPath;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $rotation = 0;

	/**
	 * @ORM\Column(type="integer")
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
	 * @ORM\Column(type="integer")
	 */
	private $centerX100 = 50;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $centerY100 = 50;

	/////

	public function getAbsolutePath() {
		$path = $this->getPath();
		return null === $path ? null : $this->getUploadRootDir().'/'.$path;
	}

	public function getPath() {
		if (null === $this->transformedPath) {
			return $this->getMasterPath();
		}
		return $this->transformedPath;
	}

	public function getMasterPath() {
		return $this->masterPath;
	}

	public function setMasterPath($masterPath) {
		$this->masterPath = $masterPath;
		return $this;
	}

	protected function getUploadRootDir() {
		// the absolute directory path where uploaded documents should be saved
		return __DIR__.'/../../../../../'.$this->getUploadDir();
	}

	protected function getUploadDir() {
		return 'uploads';
	}

	public function getWebPath() {
		$path = $this->getPath();
		return null === $path ? null : '/'.$path;
	}

	/////

	// Id /////

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

	// CreatedAt /////

	public function isMaster() {
		return is_null($this->transformedPath) || $this->transformedPath == $this->masterPath;
	}

	public function getAbsoluteTransformedPath() {
		return null === $this->transformedPath ? null : $this->getUploadRootDir().'/'.$this->transformedPath;
	}

	// Age /////

	public function getAbsoluteMasterPath() {
		return null === $this->masterPath ? null : $this->getUploadRootDir().'/'.$this->masterPath;
	}

	// User /////

	public function getId() {
		return $this->id;
	}

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	// Legend /////

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// IsMaster /////

	public function getUser() {
		return $this->user;
	}

	// MasterPath /////

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getLegend() {
		return $this->legend;
	}

	// TransformedPath /////

	public function setLegend($name) {
		$this->legend = $name;
		return $this;
	}

	public function getTransformedPath() {
		return $this->transformedPath;
	}

	// Path /////

	public function setTransformedPath($transformedPath) {
		$this->transformedPath = $transformedPath;
		return $this;
	}

	// Rotation /////

	public function getRotation() {
		return $this->rotation;
	}

	public function setRotation($rotation) {
		$this->rotation = $rotation;
		return $this;
	}

	// SortIndex /////

	public function getSortIndex() {
		return $this->sortIndex;
	}

	public function setSortIndex($sortIndex) {
		$this->sortIndex = $sortIndex;
		return $this;
	}

	// Width /////

	public function getWidth() {
		return $this->width;
	}

	public function setWidth($width) {
		$this->width = $width;
		return $this;
	}

	// Height /////

	public function getHeight() {
		return $this->height;
	}

	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}

	// HeightRatio100 /////

	public function getHeightRatio100() {
		return $this->heightRatio100;
	}

	public function setHeightRatio100($heightRatio100) {
		$this->heightRatio100 = $heightRatio100;
		return $this;
	}

	// CenterX100 /////

	public function getCenterX100() {
		return $this->centerX100;
	}

	public function setCenterX100($centerX100) {
		$this->centerX100 = $centerX100;
		return $this;
	}

	// CenterY100 /////

	public function getCenterY100() {
		return $this->centerY100;
	}

	public function setCenterY100($centerY100) {
		$this->centerY100 = $centerY100;
		return $this;
	}

}