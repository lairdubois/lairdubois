<?php

namespace Ladb\CoreBundle\Manager\Offer;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Manager\AbstractAuthoredPublicationManager;

class OfferManager extends AbstractAuthoredPublicationManager {

	const NAME = 'ladb_core.offer_offer_manager';

	/////

	public function publish(Offer $offer, $flush = true) {

		$offer->getUser()->getMeta()->incrementPrivateOfferCount(-1);
		$offer->getUser()->getMeta()->incrementPublicOfferCount();

		parent::publishPublication($offer, $flush);
	}

	public function unpublish(Offer $offer, $flush = true) {

		$offer->getUser()->getMeta()->incrementPrivateOfferCount(1);
		$offer->getUser()->getMeta()->incrementPublicOfferCount(-1);

		parent::unpublishPublication($offer, $flush);
	}

	public function delete(Offer $offer, $withWitness = true, $flush = true) {

		// Decrement user offer count
		if ($offer->getIsDraft()) {
			$offer->getUser()->getMeta()->incrementPrivateOfferCount(-1);
		} else {
			$offer->getUser()->getMeta()->incrementPublicOfferCount(-1);
		}

		parent::deletePublication($offer, $withWitness, $flush);
	}

	//////

	public function changeOwner(Offer $offer, User $user, $flush = true) {
		parent::changeOwnerPublication($offer, $user, $flush);
	}

	protected function updateUserCounterAfterChangeOwner(User $user, $by, $isPrivate) {
		if ($isPrivate) {
			$user->getMeta()->incrementPrivateOfferCount($by);
		} else {
			$user->getMeta()->incrementPublicOfferCount($by);
		}
	}

}