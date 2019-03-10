<?php

namespace Ladb\CoreBundle\Model;

interface SluggedInterface extends IdentifiableInterface {

	// Slug /////

	public function setSlug($slug);

	public function getSlug();

	public function getSluggedId();

}
