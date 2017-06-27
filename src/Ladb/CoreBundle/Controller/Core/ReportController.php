<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Report;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\TypableUtils;

/**
 * @Route("/reports")
 */
class ReportController extends Controller {

	/**
	 * @Route("/create", name="core_report_create")
	 * @Template()
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$entityType = intval($request->get('entity_type'));
		if (is_null($entityType)) {
			throw $this->createNotFoundException('No entityType');
		}
		$entityId = intval($request->get('entity_id'));
		if (is_null($entityId)) {
			throw $this->createNotFoundException('No entityId');
		}
		$reason = intval($request->get('reason'));
		if (is_null($reason)) {
			throw $this->createNotFoundException('No reason');
		}

		// Retrieve related entity

		$typableUtils = $this->get(TypableUtils::NAME);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}

		// Prepare report

		$report = new Report();
		$report->setEntityType($entityType);
		$report->setEntityId($entityId);
		$report->setUser($this->getUser());
		$report->setReason($reason);

		$om->persist($report);
		$om->flush();

		// Email notification
		$mailerUtils = $this->get(MailerUtils::NAME);
		$mailerUtils->sendReportNotificationEmailMessage($this->getUser(), $report, $entity);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('report.created'));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

}