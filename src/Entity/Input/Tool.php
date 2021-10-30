<?php

namespace App\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_tool")
 * @ORM\Entity(repositoryClass="App\Repository\Input\ToolRepository")
 */
class Tool extends AbstractInput {

}