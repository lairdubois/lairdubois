<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Utils\MailerUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Funding\Donation;
use Ladb\CoreBundle\Entity\Funding\Funding;
use Ladb\CoreBundle\Manager\Funding\FundingManager;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/financement")
 */
class FundingController extends Controller {

	/**
	 * @Route(pattern="/", name="core_funding")
	 * @Template()
	 */
	public function fundingAction() {
		$response = $this->forward('LadbCoreBundle:Funding:dashboard');
		return $response;
	}

	/**
	 * @Route(pattern="/tableau-de-bord", name="core_funding_dashboard")
	 * @Route(pattern="/tableau-de-bord/{year}/{month}", requirements={"year" = "\d+", "month" = "\d+"}, name="core_funding_dashboard_year_month")
	 * @Template()
	 */
	public function dashboardAction(Request $request, $year = null, $month = null) {
		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);

		// Retrieve parameters
		$amountEur = $request->get('amount_eur', 5);	// default amount = 5€
		$autoShow = $request->get('auto_show', false);

		if (is_null($year) || is_null($month)) {
			$fundingManager = $this->get(FundingManager::NAME);
			$funding = $fundingManager->getOrCreateCurrent();
		} else {
			$funding = $fundingRepository->findOneByYearAndMonth($year, $month);
		}
		if (is_null($funding)) {
			throw $this->createNotFoundException('Unable to find Funding entity (month='.$month.', year='.$year.').');
		}

		$prevDate = $funding->getId() == 1 ? null : new \DateTime($funding->getYear().'-'.$funding->getMonth().'-01 first day of previous month');
		$nextDate = $funding->getIsCurrent() ? null : new \DateTime($funding->getYear().'-'.$funding->getMonth().'-01 first day of next month');

		$prevPageUrl = $prevDate ? $this->get('router')->generate('core_funding_dashboard_year_month', array( 'month' => $prevDate->format('m'), 'year' => $prevDate->format('Y') )) : null;
		$nextPageUrl = $nextDate ? $this->get('router')->generate('core_funding_dashboard_year_month', array( 'month' => $nextDate->format('m'), 'year' => $nextDate->format('Y') )) : null;

		$parameters = array(
			'funding'     => $funding,
			'prevPageUrl' => $prevPageUrl,
			'nextPageUrl' => $nextPageUrl,
			'amountEur'   => $amountEur,
			'autoShow'    => $autoShow,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:dashboard-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route(pattern="/new", name="core_funding_new")
	 */
	public function newAction(Request $request) {

		// Retrieve parameters
		$amountEur = $request->get('amount_eur');

		$response = $this->forward('LadbCoreBundle:Funding:Dashboard', array(
			'amount_eur' => $amountEur,
			'auto_show' => true,
		));
		return $response;
	}

	/**
	 * @Route(pattern="/create", name="core_funding_create", defaults={"_format" = "json"})
	 * @Method("POST")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve parameters
		$amount = $request->get('amount');
		$token = $request->get('token');

		if (is_null($amount)) {
			throw $this->createNotFoundException('No amount.');
		}
		if (is_null($token)) {
			throw $this->createNotFoundException('No token.');
		}

		// Setup Stripe API
		\Stripe\Stripe::setApiKey($this->getParameter('strip_secret_key'));

		// Create a charge: this will charge the user's card
		try {

			// Create the Stripe charge
			$charge = \Stripe\Charge::create(array(
				'amount'        => $amount, // Amount in cents
				'currency'      => 'eur',
				'source'        => $token,
				'metadata'      => array('user_id' => $this->getUser()->getId()),
				"description"   => "Don au profit de L'Air du Bois",
			));

			// Retrieve the balance transaction
			$balanceTransaction = \Stripe\BalanceTransaction::retrieve($charge['balance_transaction']);

			// Create the Donation
			$donation = new Donation();
			$donation->setUser($this->getUser());
			$donation->setAmount($amount);
			$donation->setFee($balanceTransaction['fee']);
			$donation->setStripeChargeId($charge['id']);

			$om->persist($donation);

			// Update current Funding
			$fundingManager = $this->get(FundingManager::NAME);
			$funding = $fundingManager->getOrCreateCurrent();
			$funding->incrementDonationBalance($donation->getAmount() - $donation->getFee());

			$om->flush();

			// Email confirmation (after persist to have a donation id)
			$mailerUtils = $this->get(MailerUtils::NAME);
			$mailerUtils->sendFundingPaymentReceiptEmailMessage($this->getUser(), $donation);

		} catch (\Stripe\Error\Card $e) {
			return new JsonResponse(array(
				'success' => false,
				'error_code' => $e->getStripeCode(),
				'message' => $this->get('translator')->trans('funding.message.pay_error.'.$e->getStripeCode()),
			));
		}

		return new JsonResponse(array(
			'success' => true,
			'message' => $this->get('translator')->trans('funding.message.pay_success', array( '%amount%' => $amount / 100 )),
		));
	}

	/**
	 * @Route("/dons", name="core_funding_list")
	 * @Route("/dons/{filter}", requirements={"filter" = "\w+"}, name="core_funding_list_filter")
	 * @Route("/dons/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_funding_list_filter_page")
	 * @Template()
	 */
	public function listAction(Request $request, $filter = 'all', $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$donationRepository = $om->getRepository(Donation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $donationRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_funding_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'filter'        => $filter,
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'donations'     => $paginator,
			'donationCount' => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:list-xhr.html.twig', $parameters);
		}
		return $parameters;

	}

}
