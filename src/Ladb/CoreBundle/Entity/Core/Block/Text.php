<?php

namespace Ladb\CoreBundle\Entity\Core\Block;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;
use Ladb\CoreBundle\Model\HtmlBodiedTrait;
use Ladb\CoreBundle\Model\HtmlBodiedInterface;

/**
 * @ORM\Table("tbl_core_block_text")
 * @ORM\Entity
 */
class Text extends AbstractBlock implements HtmlBodiedInterface {

	use HtmlBodiedTrait;

	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @Assert\NotBlank()
	 * @Assert\Length(min=5, max=10000)
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

}