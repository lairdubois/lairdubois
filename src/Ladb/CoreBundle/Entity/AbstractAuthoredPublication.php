<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\AuthoredTrait;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractAuthoredPublication extends AbstractPublication implements AuthoredInterface {

	use AuthoredTrait;

	/**
	 * @ORM\ManyToOne(targetEntity="Ladb\CoreBundle\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	protected $user;

}