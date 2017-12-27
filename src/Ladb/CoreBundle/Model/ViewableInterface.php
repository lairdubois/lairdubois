<?php

namespace Ladb\CoreBundle\Model;

interface ViewableInterface extends IdentifiableInterface, TypableInterface {

	// ViewCount /////

	public function incrementViewCount($by = 1);

	public function setViewCount($viewCount);

	public function getViewCount();

	// IsShown /////

	public function setIsShown($isShown);

	public function getIsShown();

}