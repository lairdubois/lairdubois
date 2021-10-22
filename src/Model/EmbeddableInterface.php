<?php

namespace App\Model;

interface EmbeddableInterface extends BasicEmbeddableInterface, LicensedInterface {

	// Referrals /////

	public function addReferral(\App\Entity\Core\Referer\Referral $referral);

	public function removeReferral(\App\Entity\Core\Referer\Referral $referral);

	public function getReferrals();

	// ReferralCount /////

	public function incrementReferralCount($by = 1);

	public function getReferralCount();

}
