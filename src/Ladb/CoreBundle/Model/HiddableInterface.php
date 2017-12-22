<?php

namespace Ladb\CoreBundle\Model;

interface HiddableInterface {

	const VISIBILITY_PRIVATE = 0;
	const VISIBILITY_PROTECTED = 1;
	const VISIBILITY_PUBLIC = 2;

	// Visibility /////

	public function setVisibility($visibility);

	public function getVisibility();

	public function getIsPrivate();

	public function getIsProtected();

	public function getIsPublic();

}
