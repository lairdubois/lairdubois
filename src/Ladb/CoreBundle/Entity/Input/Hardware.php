<?php

namespace Ladb\CoreBundle\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_hardware")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Input\HardwareRepository")
 */
class Hardware extends AbstractInput {

	const CLASS_NAME = 'LadbCoreBundle:Input\Hardware';

}