<?php

namespace Ladb\CoreBundle\Fos;

use FOS\UserBundle\Util\CanonicalizerInterface;

class DisplaynameCanonicalizer implements CanonicalizerInterface {

	const NAME = 'ladb_core.fos.displayname_canonicalizer';

	public function canonicalize($string) {
		$oldLocale = setlocale(LC_ALL, '0');
		setlocale(LC_ALL, 'en_US.UTF-8');
		$result = str_replace('°', '', $string);
		$result = iconv('UTF-8', 'ASCII//TRANSLIT', $result);
		$result = preg_replace("/[^a-zA-Z0-9]/", '', $result);
		$result = strtolower($result);
		$result = trim($result, '-');
		setlocale(LC_ALL, $oldLocale);
		return $result;
	}
}