<?php

namespace Ladb\CoreBundle\Manager\Funding;

use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Funding\Charge;
use Ladb\CoreBundle\Entity\Funding\Funding;
use Ladb\CoreBundle\Manager\AbstractManager;
use Ladb\CoreBundle\Manager\AbstractPublicationManager;
use Ladb\CoreBundle\Utils\JoinableUtils;

class FundingManager extends AbstractManager {

	const NAME = 'ladb_core.funding_manager';

	/////

	public function getOrCreateCurrent() {
		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);

		$now = new \DateTime();
		$nowMonth = $now->format('m');
		$nowYear = $now->format('Y');

		$funding = $fundingRepository->findOneByYearAndMonth($nowYear, $nowMonth);
		if (is_null($funding)) {

			// Retrieve last funding
			$lastFunding = $fundingRepository->findOneLast();
			if (!is_null($lastFunding)) {

				$diffMonth = ($nowYear - $lastFunding->getYear()) * 12 + $nowMonth - $lastFunding->getMonth();

				$previousFunding = $lastFunding;
				$year = $lastFunding->getYear();
				$month = $lastFunding->getMonth();

				// Create a funding every month between last and now
				for ($i = 1; $i <= $diffMonth; $i++) {

					$month += 1;
					if ($month > 12) {
						$month = 1;
						$year++;
					}

					// Create a new funding
					$funding = new Funding();
					$funding->setYear($year);
					$funding->setMonth($month);

					foreach ($previousFunding->getCharges() as $previousCharge) {

						if (!$previousCharge->getIsRecurrent()) {
							continue;
						}

						// Duplicate charge
						$charge = new Charge();
						$charge->setDutyFreeAmount($previousCharge->getDutyFreeAmount());
						$charge->setAmount($previousCharge->getAmount());
						$charge->setType($previousCharge->getType());
						$charge->setIsRecurrent($previousCharge->getIsRecurrent());

						$funding->addCharge($charge);
						$funding->incrementChargeBalance($charge->getAmount());

					}

					// Compute the carried forward balance
					$carriedForwardBalance = $previousFunding->getEarningsBalance() - $previousFunding->getOutgoingsBalance();
					if ($carriedForwardBalance > 0) {
						$funding->setCarriedForwardBalance($carriedForwardBalance);
					}

					$om->persist($funding);

					$previousFunding = $funding;

				}

			}

			$om->flush();

		}

		return $funding;
	}

}