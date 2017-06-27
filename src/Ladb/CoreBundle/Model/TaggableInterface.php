<?php

namespace Ladb\CoreBundle\Model;

interface TaggableInterface extends TypableInterface {

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Core\Tag $tag);

	public function removeTag(\Ladb\CoreBundle\Entity\Core\Tag $tag);

	public function getTags();

}