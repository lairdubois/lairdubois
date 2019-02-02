<?php

namespace Ladb\CoreBundle\Model;

interface BasicTimestampableInterface {

	// CreatedAt /////

	public function setCreatedAt($createdAt);

	public function getCreatedAt();

	// Age /////

	public function getAge();

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt);

	public function getUpdatedAt();

	// UpdatedAge /////

	public function getUpdatedAge();

}
