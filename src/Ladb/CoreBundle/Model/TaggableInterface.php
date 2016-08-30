<?php

namespace Ladb\CoreBundle\Model;

interface TaggableInterface extends TypableInterface {

	// Tags /////

	public function addTag(\Ladb\CoreBundle\Entity\Tag $tag);

	public function removeTag(\Ladb\CoreBundle\Entity\Tag $tag);

	public function getTags();

}