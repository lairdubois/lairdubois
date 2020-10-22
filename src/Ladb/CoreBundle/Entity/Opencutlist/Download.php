<?php

namespace Ladb\CoreBundle\Entity\Opencutlist;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Model\LocalisableTrait;

/**
 * @ORM\Table("tbl_opencutlist_download")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Opencutlist\DownloadRepository")
 */
class Download implements LocalisableInterface {

	use LocalisableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Opencutlist\Download';

	const SKETCHUP_FAMILY_UNKNOW = 0;
	const SKETCHUP_FAMILY_MAKE = 1;
	const SKETCHUP_FAMILY_PRO = 2;

	const OS_UNKNOW = 0;
	const OS_WIN = 1;
	const OS_MAC = 2;

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
	protected $createdAt;

	/**
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $env;

	/**
	 * @ORM\Column(name="client_ip4", type="string")
	 */
	private $clientIp4;

	/**
	 * @ORM\Column(name="client_user_agent", type="string", nullable=true)
	 */
	private $clientUserAgent;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $analyzed = false;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 * @Assert\Length(max=100)
	 */
	private $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	private $longitude;

	/**
	 * @ORM\Column(name="client_os", type="smallint")
	 */
	private $clientOS = self::OS_UNKNOW;

	/**
	 * @ORM\Column(name="client_sketchup_family", type="smallint")
	 */
	private $clientSketchupFamily = self::SKETCHUP_FAMILY_UNKNOW;

	/**
	 * @ORM\Column(name="client_sketchup_version", type="string", nullable=true)
	 */
	private $clientSketchupVersion;

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

	// Env /////

	public function setEnv($env) {
		$this->env = $env;
		return $this;
	}

	public function getEnv() {
		return $this->env;
	}

	// ClientIp4 /////

	public function setClientIp4($clientIp4) {
		$this->clientIp4 = $clientIp4;
		return $this;
	}

	public function getClientIp4() {
		return $this->clientIp4;
	}

	// ClientUserAgent /////

	public function setClientUserAgent($clientUserAgent) {
		$this->clientUserAgent = $clientUserAgent;
		return $this;
	}

	public function getClientUserAgent() {
		return $this->clientUserAgent;
	}

	public function getTitle() {
		return $this->env;
	}

	// Analyzed /////

	public function setAnalyzed($analyzed) {
		$this->analyzed = $analyzed;
		return $this;
	}

	public function getAnalyzed() {
		return $this->analyzed;
	}

	// ClientOS /////

	public function setClientOS($clientOS) {
		$this->clientOS = $clientOS;
		return $this;
	}

	public function getClientOS() {
		return $this->clientOS;
	}

	// ClientSketchupFamily /////

	public function setClientSketchupFamily($clientSketchupFamily) {
		$this->clientSketchupFamily = $clientSketchupFamily;
		return $this;
	}

	public function getClientSketchupFamily() {
		return $this->clientSketchupFamily;
	}

	// ClientSketchupVersion /////

	public function setClientSketchupVersion($clientSketchupVersion) {
		$this->clientSketchupVersion = $clientSketchupVersion;
		return $this;
	}

	public function getClientSketchupVersion() {
		return $this->clientSketchupVersion;
	}

}
