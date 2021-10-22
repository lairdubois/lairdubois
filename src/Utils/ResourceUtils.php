<?php

namespace App\Utils;

use Elastica\Document;
use App\Entity\Core\Resource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Elastica\Query;
use App\Model\IndexableInterface;

class ResourceUtils {

	const NAME = 'ladb_core.resource_utils';

	public function getKindFromStrippedName($strippedName) {
		if (is_string($strippedName)) {
			switch (strtolower($strippedName)) {

				case 'autocad':
					return Resource::KIND_AUTOCAD;

				case 'sketchup':
					return Resource::KIND_SKETCHUP;

				case 'pdf':
					return Resource::KIND_PDF;

				case 'geogebra':
					return Resource::KIND_GEOGEBRA;

				case 'svg':
					return Resource::KIND_SVG;

				case 'freecad':
					return Resource::KIND_FREECAD;

				case 'stl':
					return Resource::KIND_STL;

				case '123design':
					return Resource::KIND_123DESIGN;

				case 'libreoffice':
					return Resource::KIND_LIBREOFFICE;

				case 'fusion360':
					return Resource::KIND_FUSION360;

				case 'collada':
					return Resource::KIND_COLLADA;

				case 'edrawing':
					return Resource::KIND_EDRAWING;

			}
		}
		return Resource::KIND_UNKNOW;
	}

}

