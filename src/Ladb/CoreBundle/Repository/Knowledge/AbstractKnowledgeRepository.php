<?php

namespace Ladb\CoreBundle\Repository\Knowledge;

use Ladb\CoreBundle\Repository\AbstractEntityRepository;

abstract class AbstractKnowledgeRepository extends AbstractEntityRepository {

	/////

	public abstract function findUserIdsById($id);

}