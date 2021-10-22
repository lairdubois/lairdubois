<?php

namespace App\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_hardware")
 * @ORM\Entity(repositoryClass="App\Repository\Input\HardwareRepository")
 */
class Hardware extends AbstractInput {

	const CLASS_NAME = 'App\Entity\Input\Hardware';

}