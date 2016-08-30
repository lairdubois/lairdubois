<?php

namespace Ladb\CoreBundle\Entity\Find\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("tbl_find_content_website")
 * @ORM\Entity
 */
class Website extends Link {

}