<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Howto\Howto;

class HowtoUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.howto_utils';

	public function computeShowParameters(Howto $howto, $referral = null) {
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

		$user = $globalUtils->getUser();

		return array(
			'howto'             => $howto,
			'userHowtos'        => $userHowtos,
			'similarHowtos'     => $similarHowtos,
			'likeContext'       => $likableUtils->getLikeContext($howto, $user),
			'watchContext'      => $watchableUtils->getWatchContext($howto, $user),
			'commentContext'    => $commentableUtils->getCommentContext($howto),
			'collectionContext' => $collectionnableUtils->getCollectionContext($howto),
			'followerContext'   => $followerUtils->getFollowerContext($howto->getUser(), $user),
			'referral'          => $referral,
		);
	}

}

