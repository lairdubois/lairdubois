<?php

namespace Ladb\CoreBundle\Entity\Block;

use Doctrine\ORM\Mapping as ORM;
use Ladb\CoreBundle\Model\BodiedInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_core_block_text")
 * @ORM\Entity
 */
class Text extends AbstractBlock implements BodiedInterface {

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=5, max=4000)
	 * @LadbAssert\NoMediaLink()
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 */
	private $htmlBody;

	// StrippedName /////

	public function getStrippedName() {
		return 'text';
	}

	// Body /////

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function getBody() {
		return $this->body;
	}

	// HtmlBody /////

	public function setHtmlBody($htmlBody) {
		$this->htmlBody = $htmlBody;
		return $this;
	}

	public function getHtmlBody() {
		return $this->htmlBody;
	}

}