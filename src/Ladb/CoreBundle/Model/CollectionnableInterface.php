<?php

namespace Ladb\CoreBundle\Model;

interface CollectionnableInterface extends TypableInterface, IdentifiableInterface {

	// CollectionCount /////

	public function incrementCollectionCount($by = 1);

	public function setCollectionCount($collectionCount);

	public function getCollectionCount();

}
