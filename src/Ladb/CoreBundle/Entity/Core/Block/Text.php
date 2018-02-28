<?php

namespace Ladb\CoreBundle\Entity\Core\Block;

use Doctrine\ORM\Mapping as ORM;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Ladb\CoreBundle\Validator\Constraints as LadbAssert;

/**
 * @ORM\Table("tbl_core_block_text")
 * @ORM\Entity
 */
class Text extends AbstractBlock implements BodiedInterface {

	use BodiedTrait;

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