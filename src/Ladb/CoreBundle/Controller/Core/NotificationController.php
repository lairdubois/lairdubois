<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Howto\Article;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Model\WatchableChildInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Notification;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/notifications")
 */
class NotificationController extends Controller {

	/**
	 * @Route("/", name="core_notification_list")
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_notification_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_notification_list_filter_page")
	 * @Template("LadbCoreBundle:Core/Notification:list-xhr.html.twig")
	 */
	public function listAction(Request $request, $filter = "recent", $page = 0) {
		$om = $this->getDoctrine()->getManager();
		$notificationRepository = $om->getRepository(Notification::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page, 9, 5);
		$limit = $paginatorUtils->computePaginatorLimit($page, 9, 5);
		$paginator = $notificationRepository->findPaginedByUser($this->getUser(), $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_notification_list_filter_page', array( 'filter' => $filter ), $page, $paginator->count());

		// Flag notification as listed

		$unlistedNotificationIds = array();
		foreach ($paginator as $notification) {
			if (!$notification->getIsListed()) {
				$unlistedNotificationIds[$notification->getId()] = true;
			}
			$notification->setIsListed(true);
		}

		$om->flush();

		// Reset user fresh notification count (only for default route)
		if ($page == 0 && $filter == "recent") {
			$this->getUser()->getMeta()->setFreshNotificationCount(0);
		}

		$om->flush();

		$parameters = array(
			'filter'                  => $filter,
			'prevPageUrl'             => $pageUrls->prev,
			'nextPageUrl'             => $pageUrls->next,
			'notifications'           => $paginator,
			'unlistedNotificationIds' => $unlistedNotificationIds,
		);

		if ($page > 0) {
			return $this->render('LadbCoreBundle:Core/Notification:list-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}", requirements={"id" = "\d+"}, name="core_notification_show")
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$notificationRepository = $om->getRepository(Notification::CLASS_NAME);

		$notification = $notificationRepository->findOneByIdJoinedOnActivity($id);
		if (is_null($notification)) {
			throw $this->createNotFoundException('Unable to find Notification entity (id='.$id.').');
		}

		// Update notification

		$notification->setIsShown(true);

		$om->flush();

		// Redirect

		$typableUtils = $this->get(TypableUtils::NAME);

		$activity = $notification->getActivity();
		$returnToUrl = $request->headers->get('referer');

		if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Comment) {
			$entity = $typableUtils->findTypable($activity->getComment()->getEntityType(), $activity->getComment()->getEntityId());
			if ($entity instanceof WatchableChildInterface) {
				$entity = $typableUtils->findTypable($entity->getParentEntityType(), $entity->getParentEntityId());
			}
			$returnToUrl = $typableUtils->getUrlAction($entity).'#_comment_'.$activity->getComment()->getId();
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Contribute) {
			// TODO
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Follow) {
			$user = $activity->getUser();
			$returnToUrl = $this->generateUrl('core_user_show', array( 'username' => $user->getUsernameCanonical() ));
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Like) {
			$entity = $typableUtils->findTypable($activity->getLike()->getEntityType(), $activity->getLike()->getEntityId());
			$returnToUrl = $typableUtils->getUrlAction($entity);
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Mention) {
			$entity = $typableUtils->findTypable($activity->getMention()->getEntityType(), $activity->getMention()->getEntityId());
			$suffix = '';
			if ($entity instanceof Comment) {
				$comment = $entity;
				$entity = $typableUtils->findTypable($comment->getEntityType(), $comment->getEntityId());
				$suffix = '#_comment_'.$comment->getId();
				if ($entity instanceof WatchableChildInterface) {
					$entity = $typableUtils->findTypable($entity->getParentEntityType(), $entity->getParentEntityId());
				}
			} else if ($entity instanceof Answer) {
				$answer = $entity;
				$entity = $answer->getQuestion();
				$suffix = '#_answer_'.$answer->getId();
			}
			$returnToUrl = $typableUtils->getUrlAction($entity).$suffix;
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Publish) {
			$entity = $typableUtils->findTypable($activity->getEntityType(), $activity->getEntityId());
			$returnToUrl = $typableUtils->getUrlAction($entity);
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Vote) {
			$entity = $typableUtils->findTypable($activity->getVote()->getParentEntityType(), $activity->getVote()->getParentEntityId());
			$returnToUrl = $typableUtils->getUrlAction($entity);
		}

		else if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Join) {
			$entity = $typableUtils->findTypable($activity->getJoin()->getEntityType(), $activity->getJoin()->getEntityId());
			$returnToUrl = $typableUtils->getUrlAction($entity);
		}

		if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Answer) {
			$entity = $activity->getAnswer()->getQuestion();
			$returnToUrl = $typableUtils->getUrlAction($entity).'#_answer_'.$activity->getAnswer()->getId();
		}

		if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Testify) {
			$entity = $activity->getTestimonial()->getSchool();
			$returnToUrl = $typableUtils->getUrlAction($entity).'#_testimonial_'.$activity->getTestimonial()->getId();
		}

		if ($activity instanceof \Ladb\CoreBundle\Entity\Core\Activity\Review) {
			$entity = $typableUtils->findTypable($activity->getReview()->getEntityType(), $activity->getReview()->getEntityId());
			$returnToUrl = $typableUtils->getUrlAction($entity).'#_review_'.$activity->getReview()->getId();
		}

		return $this->redirect($returnToUrl);
	}

}