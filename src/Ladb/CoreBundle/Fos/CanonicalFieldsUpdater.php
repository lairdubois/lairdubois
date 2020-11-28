<?php

namespace Ladb\CoreBundle\Fos;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Ladb\CoreBundle\Entity\Core\User;

class CanonicalFieldsUpdater extends \FOS\UserBundle\Util\CanonicalFieldsUpdater {

	const NAME = 'ladb_core.fos.canonical_fields_updater';

	private $displaynameCanonicalizer;

	public function __construct(CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, CanonicalizerInterface $displaynameCanonicalizer) {
		parent::__construct($usernameCanonicalizer, $emailCanonicalizer);
		$this->displaynameCanonicalizer = $displaynameCanonicalizer;
	}

	public function updateCanonicalFields(UserInterface $user) {
		parent::updateCanonicalFields($user);
		if ($user instanceof User) {
			$user->setDisplaynameCanonical($this->canonicalizeDisplayname($user->getDisplayname()));
		}
	}

	public function canonicalizeDisplayname($displayname) {
		return $this->displaynameCanonicalizer->canonicalize($displayname);
	}

}