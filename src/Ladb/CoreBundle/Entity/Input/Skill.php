<?php

namespace Ladb\CoreBundle\Entity\Input;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_input_skill")
 * @ORM\Entity(repositoryClass="Ladb\CoreBundle\Repository\Input\SkillRepository")
 */
class Skill extends AbstractInput {

	const CLASS_NAME = 'LadbCoreBundle:Input\Skill';

}