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

				$dateLast = new \DateTime($lastFunding->getYear().'-'.$lastFunding->getMonth().'-01');
				$dateCurrent = new \DateTime($nowYear.'-'.$nowMonth.'-01');
				$diff = $dateLast->diff($dateCurrent);

				$diffMonth = (($diff->format('%y') * 12) + $diff->format('%m'));

				$previousFunding = $lastFunding;

				// Create a funding every month between last and now
				for ($i = 1; $i <= $diffMonth; $i++) {

					$year = $lastFunding->getYear() + intval(($lastFunding->getMonth() - 1 + $i) / 12);
					$month = ($lastFunding->getMonth() + $i) % 12;

					// Create a new funding
					$funding = new Funding();
					$funding->setYear($year);
					$funding->setMonth($month == 0 ? 12 : $month);

					foreach ($previousFunding->getCharges() as $previousCharge) {

						// Duplicate charge
						$charge = new Charge();
						$charge->setDutyFreeAmount($previousCharge->getDutyFreeAmount());
						$charge->setAmount($previousCharge->getAmount());
						$charge->setType($previousCharge->getType());

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