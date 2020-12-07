<?php

namespace Ladb\CoreBundle\Manager\Offer;

use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class OfferManager extends AbstractPublicationManager {

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

}