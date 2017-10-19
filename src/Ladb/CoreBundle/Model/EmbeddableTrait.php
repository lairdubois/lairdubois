<?php

namespace Ladb\CoreBundle\Model;

trait EmbeddableTrait {

	use BasicEmbeddableTrait;

	// Referrals /////

	public function addReferral(\Ladb\CoreBundle\Entity\Core\Referer\Referral $referral) {
		if (!$this->referrals->contains($referral)) {
			$this->referrals[] = $referral;
			$this->referralCount = count($this->referrals);
			$referral->setEntityType($this->getType());
			$referral->setEntityId($this->getId());
		}
		return $this;
	}

	public function removeReferral(\Ladb\CoreBundle\Entity\Core\Referer\Referral $referral) {
		$this->referrals->removeElement($referral);
		$referral->setEntityType(null);
		$referral->setEntityId(null);
	}

	public function getReferrals() {
		return $this->referrals;
	}

	// ReferralCount /////

	public function incrementReferralCount($by = 1) {
		$this->referralCount += intval($by);
	}

	public function getReferralCount() {
		return $this->referralCount;
	}

}