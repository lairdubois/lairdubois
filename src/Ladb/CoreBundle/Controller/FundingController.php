<?php

namespace Ladb\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Ladb\CoreBundle\Entity\Funding\Donation;

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
	 * @Template()
	 */
	public function dashboardAction() {

		\Stripe\Stripe::setApiKey("sk_test_fcfqu5FwyxWsRVileU2SReWn");

		return array(
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
	 * @Route(pattern="/checkout/create", name="core_funding_checkout_create")
	 * @Template("LadbCoreBundle:Funding:checkout-create.html.twig")
	 */
	public function checkoutCreateAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$amount = $request->get('amount');
		$tokenId = $request->get('token_id');

		\Stripe\Stripe::setApiKey("sk_test_fcfqu5FwyxWsRVileU2SReWn");

		// Create a charge: this will charge the user's card
		try {

			// Create the Stripe charge
			$charge = \Stripe\Charge::create(array(
				'amount'      => $amount, // Amount in cents
				'currency'    => 'eur',
				'source'      => $tokenId,
				'metadata'    => array( 'user_id' => $this->getUser()->getId())
			));

		} catch (\Stripe\Error\Card $e) {
			// The card has been declined
		}

		// Create the Donation
		$donation = new Donation();
		$donation->setUser($this->getUser());
		$donation->setAmount($amount);
		$donation->setFee(0);
		$donation->setStripeCHargeIt('');

		$om->persist($donation);
		$om->flush();

		return array(
			'tokenId' => $tokenId,
		);

	}

}
