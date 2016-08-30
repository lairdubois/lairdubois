<?php

namespace Ladb\CoreBundle\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_find_content")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="integer")
 * @ORM\DiscriminatorMap({1 = "Website", 2 = "Video", 3 = "Gallery", 4 = "Event"})
 */
abstract class AbstractContent {

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	// Id /////

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function getId() {
		return $this->id;
	}

}