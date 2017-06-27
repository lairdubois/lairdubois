<?php

namespace Ladb\CoreBundle\Model;

interface EmbeddableInterface extends BasicEmbeddableInterface, LicensedInterface {

	// Referrals /////

	public function addReferral(\Ladb\CoreBundle\Entity\Core\Referer\Referral $referral);

	public function removeReferral(\Ladb\CoreBundle\Entity\Core\Referer\Referral $referral);

	public function getReferrals();

	// ReferralCount /////

	public function incrementReferralCount($by = 1);

	public function getReferralCount();

}
