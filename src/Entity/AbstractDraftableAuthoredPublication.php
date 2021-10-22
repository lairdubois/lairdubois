<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Model\DraftableInterface;
use App\Model\DraftableTrait;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractDraftableAuthoredPublication extends AbstractAuthoredPublication implements DraftableInterface {

	use DraftableTrait;

	/**
	 * @ORM\Column(name="is_draft", type="boolean")
	 */
	protected $isDraft = true;

}