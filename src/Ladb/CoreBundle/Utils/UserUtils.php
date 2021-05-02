<?php

namespace Ladb\CoreBundle\Utils;

use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Ladb\CoreBundle\Entity\AbstractPublication;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Fos\UserManager;
use Ladb\CoreBundle\Manager\Core\PictureManager;
use Ladb\CoreBundle\Model\HiddableInterface;

class UserUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.user_utils';

	const COUNTABLE_TYPES = array(
		\Ladb\CoreBundle\Entity\Wonder\Creation::TYPE,
		\Ladb\CoreBundle\Entity\Qa\Question::TYPE,
		\Ladb\CoreBundle\Entity\Wonder\Plan::TYPE,
		\Ladb\CoreBundle\Entity\Wonder\Workshop::TYPE,
		\Ladb\CoreBundle\Entity\Howto\Howto::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\Wood::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\Tool::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\Book::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\Software::TYPE,
		\Ladb\CoreBundle\Entity\Collection\Collection::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\Provider::TYPE,
		\Ladb\CoreBundle\Entity\Knowledge\School::TYPE,
		\Ladb\CoreBundle\Entity\Find\Find::TYPE,
		\Ladb\CoreBundle\Entity\Event\Event::TYPE,
		\Ladb\CoreBundle\Entity\Offer\Offer::TYPE,
		\Ladb\CoreBundle\Entity\Workflow\Workflow::TYPE,
		\Ladb\CoreBundle\Entity\Promotion\Graphic::TYPE,
		\Ladb\CoreBundle\Entity\Blog\Post::TYPE,
		\Ladb\CoreBundle\Entity\Faq\Question::TYPE,
	);

	/////

	public function _getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType) {
		return '_ladb_unlisted_counter_refresh_date_'.$entityType;
	}

	public function _setUnlistedCounterRefreshDateByEntityType($entityType, $refreshDate) {
		$globalUtils = $this->get(GlobalUtils::NAME);
		$session = $globalUtils->getSession();
		$key = $this->_getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType);
		$session->set($key, $refreshDate);
	}

	public function _getUnlistedCounterRefreshDateByEntityType($entityType, $now) {
		$globalUtils = $this->get(GlobalUtils::NAME);
		$session = $globalUtils->getSession();
		$key = $this->_getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType);
		$refreshDate = $session->get($key);
		if (is_null($refreshDate)) {
			return $now;
		}
		return $refreshDate;
	}

	/////

	public function computeUnlistedCounters(User $user, $ignoredEntityType = null) {

		$updated = false;
		foreach (self::COUNTABLE_TYPES as $entityType) {
			if ($entityType == $ignoredEntityType) {
				continue;
			}
			$updated |= $this->computeUnlistedCounterByEntityType($user, $entityType, false);
		}

		if ($updated) {
			$userManager = $this->get(UserManager::NAME);
			$userManager->updateUser($user);
		}

	}

	public function computeUnlistedCounterByEntityType(User $user, $entityType, $flush = true, $incrementUnlistedCounterRefreshTime = true) {
		if (is_null($user)) {
			return false;
		}

		$typableUtils = $this->get(TypableUtils::NAME);
		$propertyUtils = $this->get(PropertyUtils::NAME);

		$meta = $user->getMeta();
		$entityStrippedName = $typableUtils->getStrippedNameByType($entityType);

		$badgeEnabledPropertyPath = $entityStrippedName.'s_badge_enabled';
		$unlistedCountPropertyPath = 'unlisted_'.$entityStrippedName.'_count';

		// Check if badge is enabled for this entity type
		if (!$propertyUtils->getValue($meta, $badgeEnabledPropertyPath)) {

			// Reset (if necessary) count to 0 and returns
			if ($propertyUtils->getValue($meta, $unlistedCountPropertyPath) > 0) {
				$propertyUtils->setValue($meta, $unlistedCountPropertyPath, 0);
				return true;
			}

			return false;
		}

		// Check refresh date
		$now = new \DateTime();
		$refreshDate = $this->_getUnlistedCounterRefreshDateByEntityType($entityType, $now);
		if ($now < $refreshDate) {
			return false;
		}

		if ($incrementUnlistedCounterRefreshTime) {
			$this->incrementUnlistedCounterRefreshTimeByEntityType($entityType, 'PT'.mt_rand(180, 300).'S' /* = between 3 and 5 min */);
		}

		$om = $this->getDoctrine()->getManager();
		$viewRepository = $om->getRepository(View::CLASS_NAME);
		$lastView = $viewRepository->findLastCreatedByEntityTypeAndUserAndKindGreaterOrEquals($entityType, $user, View::KIND_LISTED);
		if (is_null($lastView)) {
			$lastViewDate = max(new \DateTime('2014-11-22'), $user->getCreatedAt());
		} else {
			$lastViewDate = $lastView->getCreatedAt();
		}
		if (!is_null($lastViewDate)) {
			$typableUtils = $this->get(TypableUtils::NAME);
			$entityRepository = $typableUtils->getRepositoryByType($entityType);
			if (!is_null($entityRepository)) {

				$entityClass = $typableUtils->getClassByType($entityType);
				$andWheres = array();
				$parameters = array();
				if (is_subclass_of($entityClass, '\Ladb\CoreBundle\Model\HiddableInterface')) {
					$andWheres[] = 'e.visibility = '.HiddableInterface::VISIBILITY_PUBLIC;
				}
				if (is_subclass_of($entityClass, '\Ladb\CoreBundle\Model\AuthoredInterface')) {
					$andWheres[] = 'e.user != :user';
					$parameters = array_merge($parameters, array( 'user' => $user ));
				}
				// TODO
//				if (is_subclass_of($entityClass, '\Ladb\CoreBundle\Entity\Knowledge\AbstractKnowledge')) {
//					$andWheres[] = 'e.isRejected = false';
//				}
				$count = $entityRepository->countNewerByDate($lastViewDate, $andWheres, $parameters);

				// Update count value on user entity
				if ($count != $propertyUtils->getValue($meta, $unlistedCountPropertyPath)) {

					$propertyUtils->setValue($meta, $unlistedCountPropertyPath, $count);

					if ($flush) {
						$userManager = $this->get(UserManager::NAME);
						$userManager->updateUser($user);
					}

					return true;
				}

			}
		}

		return false;	// Returns updated
	}

	public function incrementUnlistedCounterRefreshTimeByEntityType($entityType, $inc) {
		$this->_setUnlistedCounterRefreshDateByEntityType($entityType, (new \DateTime())->add(new \DateInterval($inc)));
	}

	public function resetUnlistedCounterRefreshTimeByEntityType($entityType) {
		$this->_setUnlistedCounterRefreshDateByEntityType($entityType, null);
	}

	/////

	public function createDefaultAvatar(User $user, $randomColor = true) {

		$colors = array( '006ba6', '0496ff', 'ffbc42', 'd81159', '8f2d56' );

		// Instantiate Imagine
		$imagine = new Imagine();

		// Create avatar picture
		$pictureManager = $this->get(PictureManager::NAME);
		$avatar = $pictureManager->createEmpty();
		$avatar->setUser($user);

		$width = 512;
		$height = 512;
		$fontSize = 400;
		$backgroundColor = $colors[$randomColor ? mt_rand(0, count($colors) - 1) : $user->getId() % count($colors)];
		$foregroundColor = 'fff';
		$letter = substr(empty($user->getDisplayname()) ? $user->getUsername() : $user->getDisplayname(), 0, 1);

		$palette = new RGB();

		// Output
		$avatarSize = new Box(
			$width,
			$height
		);
		$avatarImage = $imagine->create($avatarSize, $palette->color($backgroundColor));

		$font = new Font(__DIR__.'/../Resources/private/fonts/ClassicRaw.ttf', $fontSize, $palette->color($foregroundColor));
		$avatarImage->draw()->text(
			$letter,
			$font,
			new Point(
				($width - $font->box($letter)->getWidth()) / 2,
				($height - $fontSize) / 2 - 40	// 40 = workaround to center uppercase letter
			)
		);

		// Save avater image
		$avatarImage->save($avatar->getAbsoluteMasterPath(), array( 'format' => 'png', 'png_compression_level' => 9 ));

		// Add avatar to user
		$user->setAvatar($avatar);

		return $avatar;
	}

}