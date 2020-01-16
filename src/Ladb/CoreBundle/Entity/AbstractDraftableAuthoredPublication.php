<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\DraftableTrait;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractDraftableAuthoredPublication extends AbstractAuthoredPublication implements DraftableInterface {

	use DraftableTrait;

	/**
	 * @ORM\Column(name="is_draft", type="boolean")
	 */
	protected $isDraft = true;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $publishCount = 0;

	/////

	// PublishCount /////

	public function incrementPublishCount($by = 1) {
		return $this->publishCount += intval($by);
	}

	public function setPublishCount($publishCount) {
		$this->publishCount = $publishCount;
		return $this;
	}

	public function getPublishCount() {
		return $this->publishCount;
	}

}