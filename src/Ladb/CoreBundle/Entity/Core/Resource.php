<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_resource")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Resource {

	const CLASS_NAME = 'LadbCoreBundle:Core\Resource';

	const DEFAULT_ACCEPTED_FILE_TYPE = '/(\.|\/)(dwf|dwg|dxf|skp|pdf|ggb|svg|fcstd|stl|123dx|ods|xlsx|xlsm|f3d|dae)$/i';
	const DEFAULT_MAX_FILE_SIZE = 62914560;	// 60Mo

	const KIND_UNKNOW = 0;
	const KIND_AUTOCAD = 1;
	const KIND_SKETCHUP = 2;
	const KIND_PDF = 3;
	const KIND_GEOGEBRA = 4;
	const KIND_SVG = 5;
	const KIND_FREECAD = 6;
	const KIND_STL = 7;
	const KIND_123DESIGN = 8;
	const KIND_LIBREOFFICE = 9;
	const KIND_FUSION360 = 10;
	const KIND_COLLADA = 11;

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
	 * @ORM\Column(type="string", length=255)
	 */
	private $path;

	/**
	 * @ORM\Column(name="file_name", type="string", length=255)
	 */
	private $fileName;

	/**
	 * @ORM\Column(name="file_extension", type="string", length=50)
	 */
	private $fileExtension;

	/**
	 * @ORM\Column(name="file_size", type="integer")
	 */
	private $fileSize = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $kind = array( Resource::KIND_UNKNOW );

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\Picture", cascade={"all"})
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $thumbnail;

	/////

	public function getWebPath() {
		return null === $this->path ? null : '/'.$this->path;
	}

	/**
	 * @ORM\PostRemove()
	 */
	public function removeUpload() {
		if ($filename = $this->getAbsolutePath()) {
			unlink($filename);
		}
	}

	public function getAbsolutePath() {
		return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
	}

	protected function getUploadRootDir() {
		// the absolute directory path where uploaded documents should be saved
		return __DIR__.'/../../../../../'.$this->getUploadDir();
	}

	protected function getUploadDir() {
		return 'uploads';
	}

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

	// CreatedAt /////

	public function getAge() {
		return $this->getCreatedAt()->diff(new \DateTime());
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	// Age /////

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
		return $this;
	}

	// User /////

	public function getUser() {
		return $this->user;
	}

	public function setUser(\Ladb\CoreBundle\Entity\Core\User $user) {
		$this->user = $user;
		return $this;
	}

	// Path /////

	public function getPath() {
		return $this->path;
	}

	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	// FileName /////

	public function getFileName() {
		return $this->fileName;
	}

	public function setFileName($fileName) {
		$this->fileName = $fileName;
		return $this;
	}

	// FileExtension /////

	public function getFileExtension() {
		return $this->fileExtension;
	}

	public function setFileExtension($fileExtension) {
		$this->fileExtension = $fileExtension;
		return $this;
	}

	// FileSize /////

	public function getFileSize() {
		return $this->fileSize;
	}

	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;
		return $this;
	}

	// Kind /////

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	// Thumbnail /////

	public function setThumbnail(\Ladb\CoreBundle\Entity\Core\Picture $thumbnail = null) {
		$this->thumbnail = $thumbnail;
		return $this;
	}

	public function getThumbnail() {
		return $this->thumbnail;
	}

}