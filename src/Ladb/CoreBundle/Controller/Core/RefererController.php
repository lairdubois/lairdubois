<?php

namespace Ladb\CoreBundle\Controller\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Referer\Referral;
use Ladb\CoreBundle\Utils\TypableUtils;

/**
 * @Route("/referer")
 */
class RefererController extends Controller {

	/**
	 * @Route("/referral/{id}/delete", requirements={"id" = "\d+"}, name="core_referer_referral_delete")
	 */
	public function referralDeleteAction($id) {
		$om = $this->getDoctrine()->getManager();
		$referralRepository = $om->getRepository(Referral::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);

		$referral = $referralRepository->findOneById($id);
		if (is_null($referral)) {
			throw $this->createNotFoundException('Unable to find Referral entity (id='.$id.').');
		}
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_referer_referral_delete)');
		}

		$entity = $typableUtils->findTypable($referral->getEntityType(), $referral->getEntityId());
		$entity->removeReferral($referral);
		$entity->incrementReferralCount(-1);

		$om->remove($referral);
		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('referer.referral.alert.delete_success', array( '%title%' => $referral->getTitle() )));

		return $this->redirect($typableUtils->getUrlAction($entity));
	}

}