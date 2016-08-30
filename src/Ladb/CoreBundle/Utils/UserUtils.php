<?php

namespace Ladb\CoreBundle\Utils;

use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Entity\Faq\Question;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Entity\Knowledge\Wood;
use Ladb\CoreBundle\Entity\Picture;
use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Entity\View;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;

class UserUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.user_utils';

	/////

	public function _getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType) {
		return '_ladb_unlisted_counter_refresh_date_'.$entityType;
	}

	public function _getUnlistedCounterRefreshDateByEntityType($entityType) {
		$globalUtils = $this->get(GlobalUtils::NAME);
		$session = $globalUtils->getSession();
		$key = $this->_getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType);
		$refreshDate = $session->get($key);
		if (is_null($refreshDate)) {
			return new \DateTime();
		}
		return $refreshDate;
	}

	public function _setUnlistedCounterRefreshDateByEntityType($entityType, $refreshDate) {
		$globalUtils = $this->get(GlobalUtils::NAME);
		$session = $globalUtils->getSession();
		$key = $this->_getUnlistedCounterRefreshDateSessionKeyByEntityType($entityType);
		$session->set($key, $refreshDate);
	}

	/////

	public function incrementUnlistedCounterRefreshTimeByEntityType($entityType, $inc = 'PT120S' /* = 2 min */) {
		$this->_setUnlistedCounterRefreshDateByEntityType($entityType, (new \DateTime())->add(new \DateInterval($inc)));
	}

	public function computeUnlistedCounters(User $user) {

		$updated = false;
		$updated |= $this->computeUnlistedCounterByEntityType($user, Creation::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Plan::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Workshop::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Find::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Howto::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Wood::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Provider::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Post::TYPE, false);
		$updated |= $this->computeUnlistedCounterByEntityType($user, Question::TYPE, false);

		if ($updated) {
			$userManager = $this->get('fos_user.user_manager');
			$userManager->updateUser($user);
		}

	}

	public function computeUnlistedCounterByEntityType(User $user, $entityType, $flush = true) {
		if (is_null($user)) {
			return false;
		}

		// Check refresh date
		$now = new \DateTime();
		$refreshDate = $this->_getUnlistedCounterRefreshDateByEntityType($entityType);
		if ($now < $refreshDate) {
			return;
		}
		$this->incrementUnlistedCounterRefreshTimeByEntityType($entityType);

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

				$meta = $user->getMeta();
				$entityStrippedName = $typableUtils->getStrippedNameByType($entityType);
				$entityClass = $typableUtils->getClassByType($entityType);
				$andWheres = array();
				$parameters = array();
				if (is_subclass_of($entityClass, '\Ladb\CoreBundle\Model\DraftableInterface')) {
					$andWheres[] = 'e.isDraft = false';
				}
				if (is_subclass_of($entityClass, '\Ladb\CoreBundle\Model\AuthoredInterface')) {
					$andWheres[] = 'e.user != :user';
					$parameters = array_merge($parameters, array( 'user' => $user ));
				}
				$count = $entityRepository->countNewerByDate($lastViewDate, $andWheres, $parameters);

				// Update count value on user entity
				if ($count != $meta->{'getUnlisted'.ucfirst($entityStrippedName).'Count'}()) {

					$meta->{'setUnlisted'.ucfirst($entityStrippedName).'Count'}($count);

					if ($flush) {
						$userManager = $this->get('fos_user.user_manager');
						$userManager->updateUser($user);
					}

					return true;
				}

			}
		}

		return false;	// Returns updated
	}

	public function createDefaultAvatar(User $user, $randomColor = true) {

		$colors = array( '006ba6', '0496ff', 'ffbc42', 'd81159', '8f2d56' );

		// Instantiate Imagine
		$imagine = new Imagine();

		// Create avatar picture
		$avatar = new Picture();
		$avatar->setUser($user);
		$avatar->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

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