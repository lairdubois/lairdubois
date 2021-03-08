<?php

namespace Ladb\CoreBundle\Entity\Opencutlist;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\LocalisableInterface;
use Ladb\CoreBundle\Model\LocalisableTrait;

/**
 * @ORM\Table("tbl_opencutlist_access", indexes={
 *     @ORM\Index(name="IDX_ACCESS_ENTITY", columns={"client_ip4"})
 * })
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Opencutlist\AccessRepository")
 */
class Access implements LocalisableInterface {

	use LocalisableTrait;

	const CLASS_NAME = 'LadbCoreBundle:Opencutlist\Access';

	const KIND_UNKNOW = 0;
	const KIND_MANIFEST = 1;
	const KIND_DOWNLOAD = 2;
	const KIND_TUTORIALS = 3;

	const ENV_UNKNOW = 0;
	const ENV_DEV = 1;
	const ENV_PROD = 2;

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
	protected $id;

	/**
	 * @ORM\Column(name="created_at", type="datetime")
	 * @Gedmo\Timestampable(on="create")
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(type="smallint")
	 */
	protected $kind = self::KIND_UNKNOW;

	/**
	 * @ORM\Column(type="smallint")
	 */
	protected $env = self::ENV_UNKNOW;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $analyzed = false;

	/**
	 * @ORM\Column(name="client_ip4", type="string")
	 */
	protected $clientIp4;

	/**
	 * @ORM\Column(name="client_user_agent", type="string", nullable=true)
	 */
	protected $clientUserAgent;

	/**
	 * @ORM\Column(name="client_ocl_version", type="string", length=15, nullable=true)
	 */
	protected $clientOclVersion;

	/**
	 * @ORM\Column(name="client_ocl_build", type="string", length=16, nullable=true)
	 */
	protected $clientOclBuild;

	/**
	 * @ORM\Column(name="client_ocl_language", type="string", length=2, nullable=true)
	 */
	protected $clientOclLanguage;

	/**
	 * @ORM\Column(name="continent_code", type="string", length=2, nullable=true)
	 */
	protected $continentCode;

	/**
	 * @ORM\Column(name="country_code", type="string", length=2, nullable=true)
	 */
	protected $countryCode;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	protected $location;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	protected $latitude;

	/**
	 * @ORM\Column(type="float", nullable=true)
	 */
	protected $longitude;

	/**
	 * @ORM\Column(name="client_os", type="smallint")
	 */
	protected $clientOS = self::OS_UNKNOW;

	/**
	 * @ORM\Column(name="client_sketchup_family", type="smallint")
	 */
	protected $clientSketchupFamily = self::SKETCHUP_FAMILY_UNKNOW;

	/**
	 * @ORM\Column(name="client_sketchup_version", type="string", nullable=true)
	 */
	protected $clientSketchupVersion;

	/**
	 * @ORM\Column(name="client_sketchup_locale", type="string", length=5, nullable=true)
	 */
	protected $clientSketchupLocale;

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

	// Kind /////

	public static function validKind($kind) {
		if (is_string($kind)) {
			switch ($kind) {
				case 'ma':
					return self::KIND_MANIFEST;
				case 'dl':
					return self::KIND_DOWNLOAD;
				case 'tu':
					return self::KIND_TUTORIALS;
				default:
					return self::KIND_UNKNOW;
			}
		}
		$kind = intval($kind);
		if ($kind < 0 || $kind > self::KIND_TUTORIALS) {
			return self::KIND_UNKNOW;
		}
		return $kind;
	}

	public function setKind($kind) {
		$this->kind = self::validKind($kind);
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	public function getKindStrippedName() {
		switch ($this->kind) {
			case self::KIND_MANIFEST:
				return 'ma';
			case self::KIND_DOWNLOAD:
				return 'dl';
			case self::KIND_TUTORIALS:
				return 'tu';
			default:
				return '';
		}
	}

	// Env /////

	public static function validEnv($env) {
		if (is_string($env)) {
			switch ($env) {
				case 'dev':
					return self::ENV_DEV;
				case 'prod':
					return self::ENV_PROD;
				default:
					return self::ENV_UNKNOW;
			}
		}
		$env = intval($env);
		if ($env < 0 || $env > self::ENV_PROD) {
			return self::ENV_UNKNOW;
		}
		return $env;
	}

	public function setEnv($env) {
		$this->env = self::validEnv($env);
		return $this;
	}

	public function getEnv() {
		return $this->env;
	}

	public function getEnvStrippedName() {
		switch ($this->env) {
			case self::ENV_DEV:
				return 'dev';
			case self::ENV_PROD:
				return 'prod';
			default:
				return '';
		}
	}

	public function getIsEnvDev() {
		return $this->env == self::ENV_DEV;
	}

	public function getIsEnvProd() {
		return $this->env == self::ENV_PROD;
	}

	// Analyzed /////

	public function setAnalyzed($analyzed) {
		$this->analyzed = $analyzed;
		return $this;
	}

	public function getAnalyzed() {
		return $this->analyzed;
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

	// ClientOclVersion /////

	public function setClientOclVersion($clientOclVersion) {
		$this->clientOclVersion = substr($clientOclVersion, 0, 15);
		return $this;
	}

	public function getClientOclVersion() {
		return $this->clientOclVersion;
	}

	// ClientOclBuild /////

	public function setClientOclBuild($clientOclBuild) {
		$this->clientOclBuild = substr($clientOclBuild, 0, 16);
		return $this;
	}

	public function getClientOclBuild() {
		return $this->clientOclBuild;
	}

	// ClientOclLanguage /////

	public function setClientOclLanguage($clientOclLanguage) {
		$this->clientOclLanguage = substr($clientOclLanguage, 0, 2);
		return $this;
	}

	public function getClientOclLanguage() {
		return $this->clientOclLanguage;
	}

	// ContinentCode /////

	public function setContinentCode($continentCode) {
		$this->continentCode = $continentCode;
		return $this;
	}

	public function getContinentCode() {
		return $this->continentCode;
	}

	// CountryCode /////

	public function setCountryCode($countryCode) {
		$this->countryCode = $countryCode;
		return $this;
	}

	public function getCountryCode() {
		return $this->countryCode;
	}

	// ClientOS /////

	public function setClientOS($clientOS) {
		$this->clientOS = $clientOS;
		return $this;
	}

	public function getClientOS() {
		return $this->clientOS;
	}

	public function getClientOSStrippedName() {
		switch ($this->clientOS) {
			case self::OS_WIN:
				return 'win';
			case self::OS_MAC:
				return 'mac';
			default:
				return '';
		}
	}

	// ClientSketchupFamily /////

	public function setClientSketchupFamily($clientSketchupFamily) {
		$this->clientSketchupFamily = $clientSketchupFamily;
		return $this;
	}

	public function getClientSketchupFamily() {
		return $this->clientSketchupFamily;
	}

	public function getClientSketchupFamilyStrippedName() {
		switch ($this->clientSketchupFamily) {
			case self::SKETCHUP_FAMILY_MAKE:
				return 'make';
			case self::SKETCHUP_FAMILY_PRO:
				return 'pro';
			default:
				return '';
		}
	}

	// ClientSketchupVersion /////

	public function setClientSketchupVersion($clientSketchupVersion) {
		$this->clientSketchupVersion = $clientSketchupVersion;
		return $this;
	}

	public function getClientSketchupVersion() {
		return $this->clientSketchupVersion;
	}

	// ClientSketchupLocale /////

	public function setClientSketchupLocale($clientSketchupLocale) {
		$this->clientSketchupLocale = substr($clientSketchupLocale, 0, 5);
		return $this;
	}

	public function getClientSketchupLocale() {
		return $this->clientSketchupLocale;
	}

	public function getTitle() {
		return $this->env;
	}

}
