<?php

namespace Ladb\CoreBundle\Utils;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicationUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.publication_utils';

	/////

	public function checkPublicationState(AbstractPublication $publication) {
		switch ($publication->getStateCode()) {

			case AbstractPublication::STATE_DELETED:
				if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
					throw new GoneHttpException();
				}
				break;

			case AbstractPublication::STATE_CONVERTED:
				if (is_null($publication->getStateData()) || !is_array($publication->getStateData()) && count($publication->getStateData()) < 2) {
					throw new NotFoundHttpException('Unable to Creation redirection (no or bad StateData).');
				}
				$entityType = $publication->getStateData()[0];
				$entityId = $publication->getStateData()[1];
				$typableUtils = $this->get(TypableUtils::NAME);
				$typable = $typableUtils->findTypable($entityType, $entityId);
				if (is_null($typable)) {
					throw new NotFoundHttpException('Unable to find entity (type='.$entityType.' id='.$entityId.').');
				}
				return new RedirectResponse($typableUtils->getUrlAction($typable), 301);	// 301 = Moved Permanently

		}
		return null;
	}

}