<?php

namespace Ladb\CoreBundle\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_tool")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Input\ToolRepository")
 */
class Tool extends AbstractInput {

	const CLASS_NAME = 'LadbCoreBundle:Input\Tool';

}