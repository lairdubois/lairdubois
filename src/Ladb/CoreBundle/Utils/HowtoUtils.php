<?php

namespace Ladb\CoreBundle\Utils;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Core\Picture;

class HowtoUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.howto_utils';

	public function computeShowParameters(Howto $howto, $referral = null) {
		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$userHowtos = $explorableUtils->getPreviousAndNextPublishedUserExplorables($howto, $howtoRepository, $howto->getUser()->getPublishedHowtoCount());
		$similarHowtos = $explorableUtils->getSimilarExplorables($howto, 'fos_elastica.index.ladb.howto_howto', Howto::CLASS_NAME, $userHowtos);

		$globalUtils = $this->get(GlobalUtils::NAME);
		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		$user = $globalUtils->getUser();

		return array(
			'howto'           => $howto,
			'userHowtos'      => $userHowtos,
			'similarHowtos'   => $similarHowtos,
			'likeContext'     => $likableUtils->getLikeContext($howto, $user),
			'watchContext'    => $watchableUtils->getWatchContext($howto, $user),
			'commentContext'  => $commentableUtils->getCommentContext($howto),
			'followerContext' => $followerUtils->getFollowerContext($howto->getUser(), $user),
			'referral'        => $referral,
		);
	}

}

