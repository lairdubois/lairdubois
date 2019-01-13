<?php

namespace Ladb\CoreBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("sessions")
 * @ORM\Entity
 */
class Session {

	/**
	 * @ORM\Column(type="string", length=128)
	 * @ORM\Id
	 */
	private $sess_id;

	/**
	 * @ORM\Column(type="blob")
	 */
	private $sess_data;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $sess_time;

	/**
	 * @ORM\Column(type="smallint")
	 */
	private $sess_lifetime;

}