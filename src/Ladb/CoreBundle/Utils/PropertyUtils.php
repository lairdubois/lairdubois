<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\AbstractAuthoredPublication;
use Ladb\CoreBundle\Entity\Core\Activity\AbstractActivity;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Model\CommentableInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Form\Type\CommentType;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\DraftableInterface;

class PropertyUtils {

	const NAME = 'ladb_core.property_utils';

	/////

	public function camelCasePropertyAccessor($accessor, $propertyPath) {
		$terms = explode('_', $propertyPath);
		$result = $accessor;
		foreach ($terms as $term) {
			$result .= ucfirst($term);
		}
		if (empty($accessor)) {
			$result = lcfirst($result);
		}
		return $result;
	}

	/////

	public function getValue($object, $propertyPath) {
		$propertyMethod = $this->camelCasePropertyAccessor('get', $propertyPath);
		if (method_exists($object, $propertyMethod)) {
			return $object->{$propertyMethod}();
		}
		throw new \Exception('Undefined method exception '.$propertyMethod);
	}

	public function setValue($object, $propertyPath, $value) {
		$propertyMethod = $this->camelCasePropertyAccessor('set', $propertyPath);
		if (method_exists($object, $propertyMethod)) {
			$object->{ $propertyMethod }($value);
			return;
		}
		throw new \Exception('Undefined method exception '.$propertyMethod);
	}

	public function addValue($object, $propertyPath, $value) {
		$propertyMethod = $this->camelCasePropertyAccessor('add', $propertyPath);
		if (method_exists($object, $propertyMethod)) {
			$object->{ $propertyMethod }($value);
			return;
		}
		throw new \Exception('Undefined method exception '.$propertyMethod);
	}

	public function removeValue($object, $propertyPath, $value) {
		$propertyMethod = $this->camelCasePropertyAccessor('remove', $propertyPath);
		if (method_exists($object, $propertyMethod)) {
			$object->{ $propertyMethod }($value);
			return;
		}
		throw new \Exception('Undefined method exception '.$propertyMethod);
	}

}