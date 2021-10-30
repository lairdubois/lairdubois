<?php

namespace App\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_wood")
 * @ORM\Entity(repositoryClass="App\Repository\Input\WoodRepository")
 */
class Wood extends AbstractInput {

}