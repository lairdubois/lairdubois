<?php

namespace App\Model;

interface CollectionnableInterface extends TypableInterface, IdentifiableInterface {

	// PrivateCollectionCount /////

	public function incrementPrivateCollectionCount($by = 1);

	public function setPrivateCollectionCount($privateCollectionCount);

	public function getPrivateCollectionCount();

	// PublicCollectionCount /////

	public function incrementPublicCollectionCount($by = 1);

	public function setPublicCollectionCount($publicCollectionCount);

	public function getPublicCollectionCount();

}
