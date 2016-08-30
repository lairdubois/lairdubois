<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_resource")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Resource {

	const CLASS_NAME = 'LadbCoreBundle:Resource';

	const DEFAULT_ACCEPTED_FILE_TYPE = '/(\.|\/)(dwf|dwg|skp|pdf|ggb|svg|fcstd|stl|123dx|ods|xlsx|xlsm)$/i';
	const DEFAULT_MAX_FILE_SIZE = 6291456;	// 6Mo

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
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\User")
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

	/////

	public function getAbsolutePath() {
		return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
	}

	public function getWebPath() {
		return null === $this->path ? null : '/'.$this->path;
	}

	protected function getUploadRootDir() {
		// the absolute directory path where uploaded documents should be saved
		return __DIR__ . '/../../../../' . $this->getUploadDir();
	}

	protected function getUploadDir() {
		return 'uploads';
	}

	/**
	 * @ORM\PostRemove()
	 */
	public function removeUpload() {
		if ($filename = $this->getAbsolutePath()) {
			unlink($filename);
		}
	}

	/////

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

	public function setUser(\Ladb\CoreBundle\Entity\User $user) {
		$this->user = $user;
		return $this;
	}

	public function getUser() {
		return $this->user;
	}

	// Path /////

	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	public function getPath() {
		return $this->path;
	}

	// FileName /////

	public function setFileName($fileName) {
		$this->fileName = $fileName;
		return $this;
	}

	public function getFileName() {
		return $this->fileName;
	}

	// FileExtension /////

	public function setFileExtension($fileExtension) {
		$this->fileExtension = $fileExtension;
		return $this;
	}

	public function getFileExtension() {
		return $this->fileExtension;
	}

	// FileSize /////

	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;
		return $this;
	}

	public function getFileSize() {
		return $this->fileSize;
	}

}