<?php

namespace App\Entity\Knowledge\Value;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_knowledge2_value_linkable_text")
 * @ORM\Entity(repositoryClass="App\Repository\Knowledge\Value\LinkableTextRepository")
 */
class LinkableText extends Text {

	const TYPE = 23;

	const TYPE_STRIPPED_NAME = 'linkable-text';

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 * @Assert\NotBlank(groups={"mandatory"})
	 * @Assert\Length(max=255)
	 * @Assert\Url()
	 */
	private $url;

	/////

	// Type /////

	public function getType() {
		return self::TYPE;
	}

	// Url /////

	public function getUrl() {
		return $this->url;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

}