<?php

namespace Ladb\CoreBundle\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_finish")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Input\FinishRepository")
 */
class Finish extends AbstractInput {

	const CLASS_NAME = 'LadbCoreBundle:Input\Finish';

}