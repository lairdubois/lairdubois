<?php

namespace Ladb\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ladb\CoreBundle\Model\BodiedInterface;
use Ladb\CoreBundle\Model\BodiedTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_core_biography")
 * @ORM\Entity
 */
class Biography implements BodiedInterface {

	use BodiedTrait;

	const CLASS_NAME = 'LadbCoreBundle:Biography';

	/**
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Assert\Length(min=2, max=10000)
	 */
	private $body;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $htmlBody;

	/////

	// Id /////

	public function getId() {
		return $this->id;
	}

}