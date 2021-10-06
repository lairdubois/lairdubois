<?php

namespace App\Model;

interface SluggedInterface extends IdentifiableInterface {

	// Slug /////

	public function setSlug($slug);

	public function getSlug();

	public function getSluggedId();

}
