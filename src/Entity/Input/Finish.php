<?php

namespace App\Entity\Input;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("tbl_input_finish")
 * @ORM\Entity(repositoryClass="App\Repository\Input\FinishRepository")
 */
class Finish extends AbstractInput {

	const CLASS_NAME = 'App\Entity\Input\Finish';

}