<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\HiddableTrait;
use Ladb\CoreBundle\Model\MentionSourceInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractAuthoredPublication extends AbstractPublication implements AuthoredInterface, HiddableInterface, MentionSourceInterface {

	use AuthoredTrait;
	use HiddableTrait;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	protected $user;

	/**
	 * @ORM\Column(name="visibility", type="integer")
	 */
	protected $visibility = self::VISIBILITY_PRIVATE;

}