<?php

namespace App\Entity\Input;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table("tbl_input_skill")
 * @ORM\Entity(repositoryClass="App\Repository\Input\SkillRepository")
 */
class Skill extends AbstractInput {

	const CLASS_NAME = 'App\Entity\Input\Skill';

}