<?php

namespace App\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Utils\VideoHostingUtils;

/**
 * @ORM\Table("tbl_find_content_video")
 * @ORM\Entity
 */
class Video extends Link {

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $kind = VideoHostingUtils::KIND_UNKNOW;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $embedIdentifier;

	// Kind /////

	public function setKind($kind) {
		$this->kind = $kind;
		return $this;
	}

	public function getKind() {
		return $this->kind;
	}

	// EmbedIdentifier /////

	public function setEmbedIdentifier($embedIdentifier) {
		$this->embedIdentifier = $embedIdentifier;
		return $this;
	}

	public function getEmbedIdentifier() {
		return $this->embedIdentifier;
	}

}