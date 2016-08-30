<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Activity\AbstractActivity;
use Ladb\CoreBundle\Entity\Comment;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Form\Type\CommentType;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;

class PropertyUtils {

	const NAME = 'ladb_core.property_utils';

	/////

	private function _getPropertyMethod($accessor, $propertyPath) {
		$terms = explode('_', $propertyPath);
		$method = $accessor;
		foreach ($terms as $term) {
			$method .= ucfirst($term);
		}
		return $method;
	}

	/////

	public function getValue($object, $propertyPath) {
		$propertyMethod = $this->_getPropertyMethod('get', $propertyPath);
		return $object->{ $propertyMethod }();
	}

	public function setValue($object, $propertyPath, $value) {
		$propertyMethod = $this->_getPropertyMethod('set', $propertyPath);
		$object->{ $propertyMethod }($value);
	}

	public function addValue($object, $propertyPath, $value) {
		$propertyMethod = $this->_getPropertyMethod('add', $propertyPath);
		$object->{ $propertyMethod }($value);
	}

	public function removeValue($object, $propertyPath, $value) {
		$propertyMethod = $this->_getPropertyMethod('remove', $propertyPath);
		$object->{ $propertyMethod }($value);
	}

}