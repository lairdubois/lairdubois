<?php

namespace Ladb\CoreBundle\Controller\Howto;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Controller\PublicationControllerTrait;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\GlobalUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;

abstract class AbstractHowtoBasedController extends AbstractController {

	use PublicationControllerTrait;

	protected function computeShowParameters(Howto $howto, $request) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userHowtos = $explorableUtils->getPreviousAndNextPublishedUserExplorables($howto, $howtoRepository, $howto->getUser()->getMeta()->getPublicHowtoCount());
		$similarHowtos = $explorableUtils->getSimilarExplorables($howto, 'fos_elastica.index.ladb.howto_howto', Howto::CLASS_NAME, $userHowtos);

		$globalUtils = $this->get(GlobalUtils::NAME);
		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$collectionnableUtils = $this->get(CollectionnableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);
		$embaddableUtils = $this->get(EmbeddableUtils::NAME);

		$user = $globalUtils->getUser();

		return array(
			'howto'             => $howto,
			'permissionContext' => $this->getPermissionContext($howto),
			'userHowtos'        => $userHowtos,
			'similarHowtos'     => $similarHowtos,
			'likeContext'       => $likableUtils->getLikeContext($howto, $user),
			'watchContext'      => $watchableUtils->getWatchContext($howto, $user),
			'commentContext'    => $commentableUtils->getCommentContext($howto),
			'collectionContext' => $collectionnableUtils->getCollectionContext($howto),
			'followerContext'   => $followerUtils->getFollowerContext($howto->getUser(), $user),
			'referral'          => $embaddableUtils->processReferer($howto, $request),
		);
	}

}