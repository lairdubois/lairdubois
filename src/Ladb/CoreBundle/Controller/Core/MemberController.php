<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Utils\FollowerUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Utils\MemberUtils;
use Ladb\CoreBundle\Utils\ActivityUtils;
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
		$userManager = $this->get('fos_user.user_manager');
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

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
			// Check if recipient already member
			if ($memberRepository->existsByTeamAndUser($team, $recipient)) {
				$this->get('session')->getFlashBag()->add('error', '<i class="ladb-icon-warning"></i> '.$recipient.' est déjà membre.');
				continue;
			}

			// Create the invitation
			$invitation = new MemberInvitation();
			$invitation->setTeam($team);
			$invitation->setSender($this->getUser());
			$invitation->setRecipient($recipient);

			$om->persist($invitation);

			// Create activity
			$activityUtils = $this->get(ActivityUtils::NAME);
			$activityUtils->createInviteActivity($invitation, false);

			$invitationCount++;
		}

		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add($invitationCount > 0 ? 'success' : 'error', $invitationCount.' invitation envoyée');	// TODO

		return $this->redirect($this->generateUrl('core_user_show_invitations', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/invitation/{id}/delete", requirements={"id" = "\d+"}, name="core_member_invitation_delete")
	 */
	public function deleteInvitationAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);

		$invitation = $memberInvitationRepository->findOneById($id);
		if (is_null($invitation)) {
			throw $this->createNotFoundException('Invitation entity not found (id='.$id.')');
		}

		$team = $invitation->getTeam();

		if ($invitation->getRecipient() != $this->getUser() && !$memberRepository->existsByTeamAndUser($team, $this->getUser())) {
			throw $this->createNotFoundException('Not allowed (core_member_invitation_delete)');
		}

		// Delete activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByInvitation($invitation);

		$om->remove($invitation);
		$om->flush();

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', 'Invitation supprimée.');

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
			if (is_null($returnToUrl)) {
				$returnToUrl = $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical() )));
			}
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{teamId}/create", requirements={"teamId" = "\d+"}, name="core_member_create")
	 */
	public function createAction(Request $request, $teamId) {

		$this->createLock('core_member_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$team = $this->_retrieveTeam($teamId);

		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);

		// Invitation management /////

		$invitation = $memberInvitationRepository->findOneByTeamAndRecipient($team, $this->getUser());
		if (is_null($invitation)) {
			throw $this->createNotFoundException('Not allowed - User not invited (core_member_create)');
		}

		// Delete invitation activities
		$activityUtils = $this->get(ActivityUtils::NAME);
		$activityUtils->deleteActivitiesByInvitation($invitation);

		// Remove invitation
		$om->remove($invitation);

		// Follow management /////

		// Remove new member from team followers
		$followerUtils = $this->get(FollowerUtils::NAME);
		$followerUtils->deleteByFollowingUserAndUser($team, $this->getUser());

		// Member management /////

		$memberUtils = $this->get(MemberUtils::NAME);
		$memberUtils->create($team, $this->getUser());

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', 'Bienvenue dans le collectif <strong>'.$team->getDisplayName().'</strong>.');

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical() )));
	}

	/**
	 * @Route("/{teamId}/delete", requirements={"teamId" = "\d+"}, name="core_member_delete")
	 */
	public function deleteAction(Request $request, $teamId) {

		$team = $this->_retrieveTeam($teamId);

		$memberUtils = $this->get(MemberUtils::NAME);
		$memberUtils->delete($team, $this->getUser());

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

		$titleKey = $request->get('title-key');
		$route = $request->get('route');

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::class);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $memberRepository->findPaginedByUser($this->getUser(), $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_member_list_session_teams_page', array('titleKey' => $titleKey, 'route' => $route ), $page, $paginator->count());

		$parameters = array(
			'members'     => $paginator,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'titleKey'    => $titleKey,
			'route'       => $route,
		);

		if ($page > 0) {
			return $this->render('LadbCoreBundle:Core/Member:list-session-teams-n-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

}