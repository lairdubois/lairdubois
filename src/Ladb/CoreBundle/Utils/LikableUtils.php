<?php

namespace Ladb\CoreBundle\Utils;

use Ladb\CoreBundle\Entity\Core\Like;
use Ladb\CoreBundle\Model\DraftableInterface;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Model\LikableInterface;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Entity\Core\User;

class LikableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.likable_utils';

	public function deleteLikes(LikableInterface $likable, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$likes = $likeRepository->findByEntityTypeAndEntityId($likable->getType(), $likable->getId());
		foreach ($likes as $like) {
			if ((!$likable instanceof DraftableInterface) || ($likable instanceof DraftableInterface && !$likable->getIsDraft())) {
				$like->getUser()->getMeta()->incrementSentLikeCount(-1);
				if ($likable instanceof AuthoredInterface) {
					$likable->getUser()->getMeta()->incrementRecievedLikeCount(-1);
				}
			}
			$activityUtils->deleteActivitiesByLike($like);
			$om->remove($like);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function deleteLikesByUser(User $user, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$typableUtils = $this->get(TypableUtils::NAME);
		$activityUtils = $this->get(ActivityUtils::NAME);

		$likes = $likeRepository->findByUser($user);
		foreach ($likes as $like) {
			$like->getUser()->getMeta()->incrementSentLikeCount(-1);
			$likable = $typableUtils->findTypable($like->getEntityType(), $like->getEntityId());
			if (!is_null($likable) && $likable instanceof AuthoredInterface) {
				$likable->getUser()->getMeta()->incrementRecievedLikeCount(-1);
			}
			$activityUtils->deleteActivitiesByLike($like);
			$om->remove($like);
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function incrementUsersLikeCount(LikableInterface $likable, $by = 1, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);

		$likes = $likeRepository->findByEntityTypeAndEntityId($likable->getType(), $likable->getId());
		foreach ($likes as $like) {
			$like->getUser()->getMeta()->incrementSentLikeCount($by);
			if ($likable instanceof AuthoredInterface) {
				$likable->getUser()->getMeta()->incrementRecievedLikeCount($by);
			}
		}
		if ($flush) {
			$om->flush();
		}
	}

	public function getLikeContext(LikableInterface $likable, User $user = null) {
		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);

		$like = null;
		if (!is_null($user)) {
			$like = $likeRepository->findOneByEntityTypeAndEntityIdAndUser($likable->getType(), $likable->getId(), $user);
		}
		return array(
			'id'         => is_null($like) ? null : $like->getId(),
			'entityType' => $likable->getType(),
			'entityId'   => $likable->getId(),
			'isLikable'  => $likable instanceof HiddableInterface ? $likable->getIsPublic() : true,
		);
	}

	/////

	public function transferLikes(LikableInterface $likableSrc, LikableInterface $likableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);

		// Retrieve likes
		$likes = $likeRepository->findByEntityTypeAndEntityId($likableSrc->getType(), $likableSrc->getId());

		// Transfer likes
		foreach ($likes as $like) {
			$like->setEntityType($likableDest->getType());
			$like->setEntityId($likableDest->getId());
		}

		// Update counters
		$likableDest->incrementLikeCount($likableSrc->getLikeCount());
		$likableSrc->setLikeCount(0);

		if ($flush) {
			$om->flush();
		}
	}

}