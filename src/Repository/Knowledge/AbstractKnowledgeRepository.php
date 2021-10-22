<?php

namespace App\Repository\Knowledge;

use App\Repository\AbstractEntityRepository;

abstract class AbstractKnowledgeRepository extends AbstractEntityRepository {

	/////

	public abstract function findUserIdsById($id);

}