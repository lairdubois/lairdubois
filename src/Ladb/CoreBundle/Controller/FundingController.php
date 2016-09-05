<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
	public function dashboardAction($year = null, $month = null) {
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

		return array(
			'funding' => $funding,
		);
	}

	/**
	 * @Route(pattern="/statistiques", name="core_funding_statistics")
	 * @Template()
	 */
	public function statisticsAction() {
		return array(
		);
	}

	/**
	 * @Route(pattern="/create", name="core_funding_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Funding:create.html.twig")
	 */
	public function createAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		// Retrieve parameters
		$amount = $request->get('amount');
		$token = $request->get('token');

		// Setup Stripe API
		\Stripe\Stripe::setApiKey($this->getParameter('strip_secret_key'));

		// Create a charge: this will charge the user's card
		try {

			// Create the Stripe charge
			$charge = \Stripe\Charge::create(array(
				'amount'      => $amount, // Amount in cents
				'currency'    => 'eur',
				'source'      => $token,
				'metadata'    => array( 'user_id' => $this->getUser()->getId())
			));

		} catch (\Stripe\Error\Card $e) {
			// The card has been declined
		}

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

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('funding.alert.pay_success', array( '%amount%' => $amount / 100 )));

		return;
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
