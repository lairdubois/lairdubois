<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Model\AuthoredInterface;
use App\Model\AuthoredTrait;
use App\Model\HiddableInterface;
use App\Model\HiddableTrait;
use App\Model\MentionSourceInterface;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractAuthoredPublication extends AbstractPublication implements AuthoredInterface, HiddableInterface, MentionSourceInterface {

	use AuthoredTrait;
	use HiddableTrait;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Core\User")
	 * @ORM\JoinColumn(nullable=false)
	 */
	protected $user;

	/**
	 * @ORM\Column(name="visibility", type="integer")
	 */
	protected $visibility = self::VISIBILITY_PRIVATE;

}