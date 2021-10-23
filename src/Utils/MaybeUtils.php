<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Core\Report;
use App\Model\ReportableInterface;

class MaybeUtils extends AbstractContainerAwareUtils {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.GlobalUtils::class,
        ));
    }

	/////

	public function canDoIt($start = 0, $mod = 10, $key = 'global') {
		$globalUtils = $this->get(GlobalUtils::class);

		// Retrieve index from session
		$session = $globalUtils->getSession();
		$key = '_ladb_maybe_'.$key.'_index';
		$index = $session->get($key, -1);

		// Increment
		$index = $index + 1;

		// Save new index in session
		$session->set($key, $index);

		return (($index - $start) % $mod) == 0;
	}

}