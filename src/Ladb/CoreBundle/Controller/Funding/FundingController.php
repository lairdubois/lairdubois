<?php

namespace Ladb\CoreBundle\Controller\Funding;

use Ladb\CoreBundle\Entity\Funding\Charge;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Form\Type\Funding\ChargeType;
use Ladb\CoreBundle\Utils\MailerUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Funding\Donation;
use Ladb\CoreBundle\Entity\Funding\Funding;
use Ladb\CoreBundle\Manager\Funding\FundingManager;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/financement")
 */
class FundingController extends Controller {

	/**
	 * @Route("/", name="core_funding_dashboard")
	 * @Route("/{year}/{month}", requirements={"year" = "\d+", "month" = "\d+"}, name="core_funding_dashboard_year_month")
	 * @Template("LadbCoreBundle:Funding:dashboard.html.twig")
	 */
	public function dashboardAction(Request $request, $year = null, $month = null) {
		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);
		$userRepository = $om->getRepository(User::CLASS_NAME);

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
			'donorCount'  => $userRepository->countDonors(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:dashboard-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{year}/{month}/infos/{panel}.xhr", requirements={"year" = "\d+", "month" = "\d+", "panel"="[a-z-]+"}, name="core_funding_infos")
	 * @Template("LadbCoreBundle:Funding:infos-charge-balance.html.twig")
	 */
	public function infosAction(Request $request, $year = null, $month = null, $panel = null) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);

		if (is_null($year) || is_null($month)) {
			$fundingManager = $this->get(FundingManager::NAME);
			$funding = $fundingManager->getOrCreateCurrent();
		} else {
			$funding = $fundingRepository->findOneByYearAndMonth($year, $month);
		}
		if (is_null($funding)) {
			throw $this->createNotFoundException('Unable to find Funding entity (month='.$month.', year='.$year.').');
		}

		$parameters = array(
			'funding' => $funding,
		);

		switch ($panel) {
			case 'charge-balance':
				return $this->render('LadbCoreBundle:Funding:infos-charge-balance-xhr.html.twig', $parameters);
			case 'donation-fee-balance':
				return $this->render('LadbCoreBundle:Funding:infos-donation-fee-balance-xhr.html.twig', $parameters);
			case 'carried-forward-balance':
				return $this->render('LadbCoreBundle:Funding:infos-carried-forward-balance-xhr.html.twig', $parameters);
			case 'donation-balance':
				return $this->render('LadbCoreBundle:Funding:infos-donation-balance-xhr.html.twig', $parameters);
		}

		throw $this->createNotFoundException('Unknow infos panel (panel='.$panel.').');
	}

	/**
	 * @Route("/{year}/{month}/admin/charge/new", requirements={"year" = "\d+", "month" = "\d+"}, name="core_funding_admin_charge_new")
	 * @Template("LadbCoreBundle:Funding:charge-new-xhr.html.twig")
	 */
	public function chargeNewAction(Request $request, $year = null, $month = null) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);

		$funding = $fundingRepository->findOneByYearAndMonth($year, $month);
		if (is_null($funding)) {
			throw $this->createNotFoundException('Unable to find Funding entity (month='.$month.', year='.$year.').');
		}

		$charge = new Charge();
		$form = $this->createForm(ChargeType::class, $charge);

		return array(
			'form' => $form->createView(),
			'funding' => $funding,
		);
	}

	/**
	 * @Route("/{year}/{month}/admin/charge/create", requirements={"year" = "\d+", "month" = "\d+"}, methods={"POST"}, name="core_funding_admin_charge_create")
	 * @Template("LadbCoreBundle:Funding:charge-new-xhr.html.twig")
	 */
	public function chargeCreateAction(Request $request, $year = null, $month = null) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$fundingRepository = $om->getRepository(Funding::CLASS_NAME);

		$funding = $fundingRepository->findOneByYearAndMonth($year, $month);
		if (is_null($funding)) {
			throw $this->createNotFoundException('Unable to find Funding entity (month='.$month.', year='.$year.').');
		}

		$charge = new Charge();
		$form = $this->createForm(ChargeType::class, $charge);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Update funding charge balance
			$funding->addCharge($charge);
			$funding->incrementChargeBalance($charge->getAmount());

			// Compute carriedForwardBalance to all next fundings
			$fundingManager = $this->get(FundingManager::NAME);
			$fundingManager->updateCarriedForwardBalancesFrom($funding, false);

			$om->flush();

			return $this->render('LadbCoreBundle:Funding:charge-create-xhr.html.twig', array(
				'charge' => $charge,
			));
		}

		return array(
			'form' => $form->createView(),
			'funding' => $funding,
		);
	}

	/**
	 * @Route("/admin/charge/{id}/edit", requirements={"id" = "\d+"}, name="core_funding_admin_charge_edit")
	 * @Template("LadbCoreBundle:Funding:charge-edit-xhr.html.twig")
	 */
	public function chargeEditAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$chargeRepository = $om->getRepository(Charge::CLASS_NAME);

		$charge = $chargeRepository->findOneById($id);
		if (is_null($charge)) {
			throw $this->createNotFoundException('Unable to find Charge entity (id='.$id.').');
		}

		$form = $this->createForm(ChargeType::class, $charge);

		return array(
			'form'   => $form->createView(),
			'charge' => $charge,
		);
	}

	/**
	 * @Route("/admin/charge/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_funding_admin_charge_update")
	 * @Template("LadbCoreBundle:Funding:charge-update-xhr.html.twig")
	 */
	public function chargeUpdateAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$chargeRepository = $om->getRepository(Charge::CLASS_NAME);

		$charge = $chargeRepository->findOneById($id);
		if (is_null($charge)) {
			throw $this->createNotFoundException('Unable to find Charge entity (id='.$id.').');
		}

		$previousChargeAmount = $charge->getAmount();

		$form = $this->createForm(ChargeType::class, $charge);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Update funding charge balance
			$funding = $charge->getFunding();
			$funding->incrementChargeBalance($charge->getAmount() - $previousChargeAmount);

			// Compute carriedForwardBalance to all next fundings
			$fundingManager = $this->get(FundingManager::NAME);
			$fundingManager->updateCarriedForwardBalancesFrom($funding, false);

			$om->flush();

			return $this->render('LadbCoreBundle:Funding:charge-update-xhr.html.twig', array(
				'charge' => $charge,
			));
		}

		return array(
			'form'   => $form->createView(),
			'charge' => $charge,
		);
	}

	/**
	 * @Route("/admin/charge/{id}/delete", requirements={"id" = "\d+"}, name="core_funding_admin_charge_delete")
	 * @Template("LadbCoreBundle:Funding:charge-delete-xhr.html.twig")
	 */
	public function chargeDeleteAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$om = $this->getDoctrine()->getManager();
		$chargeRepository = $om->getRepository(Charge::CLASS_NAME);

		$charge = $chargeRepository->findOneById($id);
		if (is_null($charge)) {
			throw $this->createNotFoundException('Unable to find Charge entity (id='.$id.').');
		}

		// Update funding balance
		$funding = $charge->getFunding();
		$funding->incrementChargeBalance(-$charge->getAmount());
		$funding->removeCharge($charge);

		// Compute carriedForwardBalance to all next fundings
		$fundingManager = $this->get(FundingManager::NAME);
		$fundingManager->updateCarriedForwardBalancesFrom($funding, false);

		$om->remove($charge);
		$om->flush();

		return;
	}

	/**
	 * @Route("/donation/new", name="core_funding_donation_new")
	 * @Template("LadbCoreBundle:Funding:donation-new.html.twig")
	 */
	public function donationNewAction(Request $request) {

		// Retrieve parameters
		$amountEur = intval($request->get('amount_eur', 5));	// Default 5 euros

		if ($request->isXmlHttpRequest()) {

			$minAmountEur = $this->getParameter('funding_min_amount_eur');
			$maxAmountEur = $this->getParameter('funding_max_amount_eur');

			return array(
				'amountEur'    => $amountEur,
				'feeEur'       => $amountEur * 0.014 + 0.25,
				'validAmount'  => $amountEur >= $minAmountEur && $amountEur <= $maxAmountEur,
				'minAmountEur' => $minAmountEur,
				'maxAmountEur' => $maxAmountEur,
			);
		}

		return $this->redirect($this->generateUrl('core_funding_dashboard', array(
			'amount_eur' => $amountEur,
			'auto_show' => true,
		)));
	}

	/**
	 * @Route("/donation/create", name="core_funding_donation_create", methods={"POST"}, defaults={"_format" = "json"})
	 */
	public function donationCreateAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_funding_donation_create)');
		}
		if (!$this->getUser()->getEmailConfirmed()) {
			throw $this->createNotFoundException('User email is not confirmed.');
		}

		$minAmount = $this->getParameter('funding_min_amount_eur') * 100;
		$maxAmount = $this->getParameter('funding_max_amount_eur') * 100;

		// Retrieve parameters
		$amount = $request->get('amount');
		$token = $request->get('token');

		if (is_null($amount)) {
			throw $this->createNotFoundException('No amount.');
		}
		if ($amount < $minAmount || $amount > $maxAmount) {
			throw $this->createNotFoundException('Amount is out of range ($amount='.$amount.')');
		}
		if (is_null($token)) {
			throw $this->createNotFoundException('No token.');
		}

		// Setup Stripe API
		\Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));

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

		} catch (\Stripe\Error\Card $e) {
			return new JsonResponse(array(
				'success' => false,
				'error_code' => $e->getStripeCode(),
				'message' => $this->get('translator')->trans('funding.message.pay_error.'.$e->getStripeCode()),
			));
		}

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
		$funding->incrementDonationFeeBalance($donation->getFee());
		$funding->incrementDonationBalance($donation->getAmount());
		$funding->incrementDonationCount();

		// Increment user donation stats
		$this->getUser()->getMeta()->incrementDonationCount();
		$this->getUser()->getMeta()->incrementDonationBalance($donation->getAmount());
		$this->getUser()->getMeta()->incrementDonationFeeBalance($donation->getFee());

		$om->flush();	// Flush to be sure that donation id is generated

		// Generate donation hashid
		$hashids = new \Hashids\Hashids($this->getParameter('secret'), 5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
		$donation->setHashid($hashids->encode($donation->getId()));

		$om->flush();

		// Email confirmation (after persist to have a donation id)
		$mailerUtils = $this->get(MailerUtils::NAME);
		$mailerUtils->sendFundingPaymentReceiptEmailMessage($this->getUser(), $donation);

		// Email notification (to admin)
		$mailerUtils->sendNewDonationNotificationEmailMessage($this->getUser(), $donation);

		return new JsonResponse(array(
			'success' => true,
			'content' => $this->get('templating')->render('LadbCoreBundle:Funding:donation-create.html.twig', array( 'donation' => $donation )),
		));
	}

	/**
	 * @Route("/donateurs", name="core_funding_donors")
	 * @Route("/donateurs/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_funding_donors_page")
	 * @Template("LadbCoreBundle:Funding:donors.html.twig")
	 */
	public function donorsAction(Request $request, $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $userRepository->findDonorsPagined($offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_funding_donors_page', array(), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'donors'      => $paginator,
			'donorCount'  => $paginator->count(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:donors-xhr.html.twig', $parameters);
		}
		return $parameters;

	}

	/**
	 * @Route("/mes-dons", name="core_funding_user_donation_list")
	 * @Route("/mes-dons/{filter}", requirements={"filter" = "\w+"}, name="core_funding_user_donation_list_filter")
	 * @Route("/mes-dons/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_funding_user_donation_list_filter_page")
	 * @Template("LadbCoreBundle:Funding:user-donation-list.html.twig")
	 */
	public function userDonationListAction(Request $request, $filter = 'recent', $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$donationRepository = $om->getRepository(Donation::CLASS_NAME);
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $donationRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_funding_user_donation_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$parameters = array(
			'filter'        => $filter,
			'prevPageUrl'   => $pageUrls->prev,
			'nextPageUrl'   => $pageUrls->next,
			'donations'     => $paginator,
			'donationCount' => $paginator->count(),
			'donorCount'    => $userRepository->countDonors(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:user-donation-list-xhr.html.twig', $parameters);
		}
		return $parameters;

	}

	/**
	 * @Route("/admin/dons", name="core_funding_admin_donation_list")
	 * @Route("/admin/dons/{filter}", requirements={"filter" = "\w+"}, name="core_funding_admin_donation_list_filter")
	 * @Route("/admin/dons/{filter}/{page}", requirements={"filter" = "\w+", "page" = "\d+"}, name="core_funding_admin_donation_list_filter_page")
	 * @Template("LadbCoreBundle:Funding:donation-list.html.twig")
	 */
	public function donationListAction(Request $request, $filter = 'recent', $page = 0) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$donationRepository = $om->getRepository(Donation::CLASS_NAME);
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 20, 20);
		$limit = $paginatorUtils->computePaginatorLimit($page, 20, 20);
		$paginator = $donationRepository->findPagined($offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_funding_admin_donation_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count(), 20, 20);

		$donationBalance = $donationRepository->sumAmounts();
		$donationFeeBalance = $donationRepository->sumFees();

		$parameters = array(
			'filter'                => $filter,
			'prevPageUrl'           => $pageUrls->prev,
			'nextPageUrl'           => $pageUrls->next,
			'donations'             => $paginator,
			'donationCount'         => $paginator->count(),
			'donationBalanceEur'    => $donationBalance / 100,
			'donationFeeBalanceEur' => $donationFeeBalance / 100,
			'donorCount'            => $userRepository->countDonors(),
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Funding:donation-list-xhr.html.twig', $parameters);
		}
		return $parameters;

	}

}
