<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as LadbAssert;
use App\Utils\VideoHostingUtils;

/**
 * @ORM\Table("tbl_knowledge2_value_video")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\VideoRepository")
 * @LadbAssert\SupportedVideoHosting()
 */
class Video extends BaseValue {

	const TYPE = 24;

	const TYPE_STRIPPED_NAME = 'video';

	/**
	 * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=255)
	 * @Assert\Url()
	 */
	protected $data;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = VideoHostingUtils::KIND_UNKNOW;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $embedIdentifier;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Kind /////

	public function getKind() {
		return $this->kind;
	}

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	// EmbedIdentifier /////

	public function getEmbedIdentifier() {
		return $this->embedIdentifier;
	}

	public function setEmbedIdentifier($embedIdentifier) {
		$this->embedIdentifier = $embedIdentifier;
		return $this;
	}

	/////

	// IsDisplayGrid /////

	public function getIsDisplayGrid() {
		return true;
	}

}