<?php

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Core\Report;
use App\Model\ReportableInterface;

class ReportableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.reportable_utils';

	/////

	public function deleteReports(ReportableInterface $likable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$reportRepository = $om->getRepository(Report::CLASS_NAME);
		$reports = $reportRepository->findByEntityTypeAndEntityId($likable->getType(), $likable->getId());
		foreach ($reports as $report) {
			$om->remove($report);
		}
		if ($flush) {
			$om->flush();
		}
	}

	/////

	public function transferReports(ReportableInterface $reportableSrc, ReportableInterface $reportableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$reportRepository = $om->getRepository(Report::CLASS_NAME);

		// Retrieve reports
		$reports = $reportRepository->findByEntityTypeAndEntityId($reportableSrc->getType(), $reportableSrc->getId());

		// Transfer reports
		foreach ($reports as $report) {
			$report->setEntityType($reportableDest->getType());
			$report->setEntityId($reportableDest->getId());
		}

		if ($flush) {
			$om->flush();
		}
	}

}