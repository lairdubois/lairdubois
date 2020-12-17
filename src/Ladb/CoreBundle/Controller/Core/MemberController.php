<?php

namespace Ladb\CoreBundle\Controller\Core;

use Elastica\Exception\NotFoundException;
use Ladb\CoreBundle\Fos\UserManager;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Utils\WebpushNotificationUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\MemberRequest;
use Ladb\CoreBundle\Manager\Core\MemberInvitationManager;
use Ladb\CoreBundle\Manager\Core\MemberManager;
use Ladb\CoreBundle\Manager\Core\MemberRequestManager;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Core\MemberInvitation;
use Ladb\CoreBundle\Utils\PaginatorUtils;

/**
 * @Route("/members")
 */
class MemberController extends AbstractController {

	private function _retrieveTeam($teamId) {
		$om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);

		$team = $userRepository->findOneById($teamId);
		if (is_null($team)) {
			throw $this->createNotFoundException('Unable to find Team entity (id='.$teamId.').');
		}
		if (!$team->isEnabled()) {
			throw $this->createNotFoundException('Team not enabled');
		}

		return $team;
	}

	/////

	/**
	 * @Route("/{teamId}/invitation/new", requirements={"teamId" = "\d+"}, name="core_member_invitation_new")
	 * @Template("LadbCoreBundle:Core/Member:invitation-new.html.twig")
	 */
	public function newInvitationAction($teamId) {

		$team = $this->_retrieveTeam($teamId);

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		if (!$memberRepository->existsByTeamAndUser($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_member_invitation_new)');
		}

		return array(
			'team' => $team,
		);
	}

	/**
	 * @Route("/{teamId}/invitation/create", requirements={"teamId" = "\d+"}, name="core_member_invitation_create")
	 */
	public function createInvitationAction(Request $request, $teamId) {

		$team = $this->_retrieveTeam($teamId);

		$this->createLock('core_member_invitation_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$userManager = $this->get(UserManager::NAME);
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		if (!$memberRepository->existsByTeamAndUser($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_member_invitation_create)');
		}

		$recipientUsernames = $request->get('recipients');

		$invitationCount = 0;
		foreach (explode(',', $recipientUsernames) as $username) {

			if (strlen($username) == 0) {
				continue;
			}

			$recipient = $userManager->findUserByUsername($username);
			// Check if recipient exists or is enabled or is team
			if (is_null($recipient) || !$recipient->isEnabled() || $recipient->getIsTeam()) {
				$this->get('session')->getFlashBag()->add('error', '<i class="ladb-icon-warning"></i> '.$recipient.' est invalide.');
				continue;
			}
			// Check if recipient already invited
			if ($memberInvitationRepository->existsByTeamAndRecipient($team, $recipient)) {
				$this->get('session')->getFlashBag()->add('error', '<i class="ladb-icon-warning"></i> '.$recipient.' est déjà invité.');
				continue;
			}
			// Check if recipient already requested
			if ($memberRequestRepository->existsByTeamAndSender($team, $recipient)) {
				$this->get('session')->getFlashBag()->add('error', '<i class="ladb-icon-warning"></i> '.$recipient.' a déjà envoyé une demande.');
				continue;
			}
			// Check if recipient already member
			if ($memberRepository->existsByTeamAndUser($team, $recipient)) {
				$this->get('session')->getFlashBag()->add('error', '<i class="ladb-icon-warning"></i> '.$recipient.' est déjà membre.');
				continue;
			}

			// Create invitation
			$memberInvitationManager = $this->get(MemberInvitationManager::NAME);
			$memberInvitation = $memberInvitationManager->create($team, $this->getUser(), $recipient, false);

			// Publish a webpush notification in queue
			$webpushNotificationUtils = $this->get(WebpushNotificationUtils::class);
			$webpushNotificationUtils->enqueueNewMemberInvitationNotification($memberInvitation);

			$invitationCount++;
		}

		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add($invitationCount > 0 ? 'success' : 'error', $invitationCount.' invitation envoyée');	// TODO

		return $this->redirect($this->generateUrl($invitationCount > 0 ? 'core_user_show_invitations' : 'core_user_show', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/invitation/{id}/accept", requirements={"id" = "\d+"}, name="core_member_invitation_accept")
	 */
	public function acceptInvitationAction($id) {
		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);

		$memberInvitation = $memberInvitationRepository->findOneById($id);
		if (is_null($memberInvitation)) {
			throw $this->createNotFoundException('Invitation entity not found (id='.$id.')');
		}
		if ($memberInvitation->getRecipient() != $this->getUser() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_member_invitation_accept)');
		}

		// Delete invitation
		$memberInvitationManager = $this->get(MemberInvitationManager::NAME);
		$memberInvitationManager->delete($memberInvitation);

		return $this->createAction($memberInvitation->getTeam(), $memberInvitation->getRecipient());
	}

	/**
	 * @Route("/invitation/{id}/delete", requirements={"id" = "\d+"}, name="core_member_invitation_delete")
	 */
	public function deleteInvitationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$memberInvitation = $memberInvitationRepository->findOneById($id);
		if (is_null($memberInvitation)) {
			throw $this->createNotFoundException('Invitation entity not found (id='.$id.')');
		}
		if ($memberInvitation->getRecipient() != $this->getUser() && !$memberRepository->existsByTeamAndUser($memberInvitation->getTeam(), $this->getUser()) && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_member_invitation_delete)');
		}

		// Delete invitation
		$memberInvitationManager = $this->get(MemberInvitationManager::NAME);
		$memberInvitationManager->delete($memberInvitation);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', 'Invitation supprimée.');

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
			if (is_null($returnToUrl)) {
				$returnToUrl = $this->redirect($this->generateUrl('core_user_show', array( 'username' => $memberInvitation->getTeam()->getUsernameCanonical() )));
			}
		}

		return $this->redirect($returnToUrl);
	}

	/////

	/**
	 * @Route("/{teamId}/request/new", requirements={"teamId" = "\d+"}, name="core_member_request_new")
	 * @Template("LadbCoreBundle:Core/Member:request-new.html.twig")
	 */
	public function newRequestAction($teamId) {

		$team = $this->_retrieveTeam($teamId);
		if (!$team->getMeta()->getRequestEnabled()) {
			throw $this->createNotFoundException('Not allowed - Request disabled (core_member_request_new)');
		}

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		if ($memberRepository->existsByTeamAndUser($team, $this->getUser())) {
			throw $this->createNotFoundException('Already member (core_member_request_new)');
		}

		return array(
			'team' => $team,
		);
	}

	/**
	 * @Route("/{teamId}/request/create", requirements={"teamId" = "\d+"}, name="core_member_request_create")
	 */
	public function createRequestAction(Request $request, $teamId) {

		$team = $this->_retrieveTeam($teamId);
		if (!$team->getMeta()->getRequestEnabled()) {
			throw $this->createNotFoundException('Not allowed - Request disabled (core_member_request_create)');
		}

		$this->createLock('core_member_request_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		if ($memberRepository->existsByTeamAndUser($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed - Already member (core_member_request_create)');
		}
		if ($memberInvitationRepository->existsByTeamAndRecipient($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed - Already invited (core_member_request_create)');
		}
		if ($memberRequestRepository->existsByTeamAndSender($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed - Already requested (core_member_request_create)');
		}

		// Create request
		$memberRequestManager = $this->get(MemberRequestManager::NAME);
		$memberRequestManager->create($team, $this->getUser(), false);

		$om->flush();

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/request/{id}/accept", requirements={"id" = "\d+"}, name="core_member_request_accept")
	 */
	public function acceptRequestAction($id) {
		$om = $this->getDoctrine()->getManager();
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$memberRequest = $memberRequestRepository->findOneById($id);
		if (is_null($memberRequest)) {
			throw $this->createNotFoundException('Request entity not found (id='.$id.')');
		}
		if ($memberRequest->getSender() != $this->getUser() && !$memberRepository->existsByTeamAndUser($memberRequest->getTeam(), $this->getUser()) && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_member_request_accept)');
		}

		// Delete request
		$memberRequestManager = $this->get(MemberRequestManager::NAME);
		$memberRequestManager->delete($memberRequest);

		return $this->createAction($memberRequest->getTeam(), $memberRequest->getSender());
	}

	/**
	 * @Route("/request/{id}/delete", requirements={"id" = "\d+"}, name="core_member_request_delete")
	 */
	public function deleteRequestAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$memberRequest = $memberRequestRepository->findOneById($id);
		if (is_null($memberRequest)) {
			throw $this->createNotFoundException('Request entity not found (id='.$id.')');
		}
		if ($memberRequest->getSender() != $this->getUser() && !$memberRepository->existsByTeamAndUser($memberRequest->getTeam(), $this->getUser()) && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_member_request_delete)');
		}

		// Delete request
		$memberRequestManager = $this->get(MemberRequestManager::NAME);
		$memberRequestManager->delete($memberRequest);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', 'Demande supprimée.');

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
			if (is_null($returnToUrl)) {
				$returnToUrl = $this->redirect($this->generateUrl('core_user_show', array( 'username' => $memberRequest->getTeam()->getUsernameCanonical() )));
			}
		}

		return $this->redirect($returnToUrl);
	}

	/////

	public function createAction(User $team, User $user) {

		$this->createLock('core_member_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		// Follow management /////

		// Remove new member from team followers
		$followerUtils = $this->get(FollowerUtils::NAME);
		$followerUtils->deleteByFollowingUserAndUser($team, $user);

		// Member management /////

		$memberManager = $this->get(MemberManager::NAME);
		$member = $memberManager->create($team, $user);

		// Search index update
		$searchUtils = $this->container->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($member->getTeam());

		// Flashbag
		if ($user == $this->getUser()) {
			$this->get('session')->getFlashBag()->add('success', 'Bienvenue dans le collectif <strong>'.$team->getDisplayName().'</strong>.');
		} else {
			$this->get('session')->getFlashBag()->add('success', '<strong>'.$user->getDisplayName().'</strong> a rejoint le collectif <strong>'.$team->getDisplayName().'</strong>.');
		}

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/{teamId}/delete", requirements={"teamId" = "\d+"}, name="core_member_delete")
	 */
	public function deleteAction(Request $request, $teamId) {

		$team = $this->_retrieveTeam($teamId);
		if ($team->getMeta()->getMemberCount() <= 1 ) {
			throw new NotFoundException('Not allowed - last survivor (core_member_delete)');
		}

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$member = $memberRepository->findOneByTeamAndUser($team, $this->getUser());
		if (!is_null($member)) {

			// Delete member
			$memberManager = $this->get(MemberManager::NAME);
			$memberManager->delete($member);

			// Search index update
			$searchUtils = $this->container->get(SearchUtils::NAME);
			$searchUtils->replaceEntityInIndex($member->getTeam());

		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', 'Vous avez quitté le collectif <strong>'.$team->getDisplayName().'</strong>.');

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/", name="core_member_list_session_teams")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_member_list_session_teams_page")
	 * @Template("LadbCoreBundle:Core/Member:list-session-teams-xhr.html.twig")
	 */
	public function listSessionTeamsAction(Request $request, $page = 0) {

		$titleTransKey = $request->get('title-trans-key');
		$route = $request->get('route');

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::class);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $memberRepository->findPaginedByUser($this->getUser(), $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_member_list_session_teams_page', array('titleTransKey' => $titleTransKey, 'route' => $route ), $page, $paginator->count());

		$parameters = array(
			'members'     => $paginator,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'titleTransKey'    => $titleTransKey,
			'route'       => $route,
		);

		if ($page > 0) {
			return $this->render('LadbCoreBundle:Core/Member:list-session-teams-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

}