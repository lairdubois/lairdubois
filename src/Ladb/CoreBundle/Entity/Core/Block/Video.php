<?php

namespace Ladb\CoreBundle\Entity\Core\Block;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Utils\VideoHostingUtils;

/**
 * @ORM\Table("tbl_core_block_video")
 * @ORM\Entity
 * @LadbAssert\SupportedVideoHosting()
 */
class Video extends AbstractBlock {

	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Url
	 */
	private $url;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = VideoHostingUtils::KIND_UNKNOW;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $embedIdentifier;

	// StrippedName /////

	public function getStrippedName() {
		return 'video';
	}

	// Url /////

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
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

}