<?php

namespace App\Controller\Core;

use App\Manager\Core\UserManager;
use Elastica\Exception\NotFoundException;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use App\Controller\AbstractController;
use App\Controller\UserControllerTrait;
use App\Entity\Core\Feedback;
use App\Entity\Core\Member;
use App\Entity\Core\MemberInvitation;
use App\Entity\Core\MemberRequest;
use App\Entity\Core\Review;
use App\Entity\Core\User;
use App\Entity\Core\Vote;
use App\Entity\Offer\Offer;
use App\Form\Type\Core\UserTeamSettingsType;
use App\Form\Type\Core\UserTeamType;
use App\Manager\Core\MemberInvitationManager;
use App\Manager\Core\MemberManager;
use App\Manager\Core\MemberRequestManager;
use App\Utils\PropertyUtils;
use App\Utils\TypableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Entity\Core\UserWitness;
use App\Entity\Knowledge\School\Testimonial;
use App\Entity\Promotion\Graphic;
use App\Entity\Qa\Answer;
use App\Entity\Qa\Question;
use App\Entity\Workflow\Workflow;
use App\Entity\Core\Comment;
use App\Entity\Find\Find;
use App\Entity\Core\Follower;
use App\Entity\Howto\Howto;
use App\Entity\Core\Like;
use App\Entity\Wonder\Creation;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Workshop;
use App\Entity\Core\Registration;
use App\Form\Type\Core\UserSettingsType;
use App\Utils\CryptoUtils;
use App\Utils\PaginatorUtils;
use App\Utils\FollowerUtils;
use App\Utils\LocalisableUtils;
use App\Utils\MailerUtils;
use App\Utils\SearchUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\LikableUtils;
use App\Utils\UserUtils;

/**
 * @Route("/")
 */
class UserController extends AbstractController {

	use UserControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.UserUtils::class,
            '?'.MemberInvitationManager::class,
            '?'.MemberRequestManager::class,
            '?'.MemberManager::class,
        ));
    }

    /////

    private function _isGrantedOwner(User $user) {
		if ($user->getIsTeam() && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$om = $this->getDoctrine()->getManager();
			$memberRepository = $om->getRepository(Member::CLASS_NAME);
			return $memberRepository->existsByTeamAndUser($user, $this->getUser());

		}
		return $user == $this->getUser();
	}

	private function _getInvitation(User $user) {
		if ($user->getIsTeam() && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$om = $this->getDoctrine()->getManager();
			$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
			return $memberInvitationRepository->findOneByTeamAndRecipient($user, $this->getUser());

		}
		return null;
	}

	private function _getRequest(User $user) {
		if ($user->getIsTeam() && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$om = $this->getDoctrine()->getManager();
			$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
			return $memberRequestRepository->findOneByTeamAndSender($user, $this->getUser());

		}
		return null;
	}

	private function _fillCommonShowParameters(User $user, $parameters, $isGrantedOwner = null) {

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$memberInvitation = $this->_getInvitation($user, $this->getUser());
			if (!is_null($memberInvitation)) {

				// Flashbag
				$this->get('session')->getFlashBag()->add('info', $this->render('Core/Member/_invitation-alert.part.html.twig', array(
					'invitation' => $memberInvitation,
				)));

			}

			$memberRequest = $this->_getRequest($user, $this->getUser());
			if (!is_null($memberRequest)) {

				// Flashbag
				$this->get('session')->getFlashBag()->add('info', $this->render('Core/Member/_request-alert.part.html.twig', array(
					'request' => $memberRequest,
				)));

			}

		}

		$followerUtils = $this->get(FollowerUtils::class);

		return array_merge($parameters, array(
			'user'            => $user,
			'isGrantedOwner'  => is_null($isGrantedOwner) ? $this->_isGrantedOwner($user) : $isGrantedOwner,
			'invitation'      => $this->_getInvitation($user),
			'request'         => $this->_getRequest($user),
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/////

	/**
	 * @Route("/email/check", name="core_user_email_check")
	 * @Template("Core/User/emailCheck.html.twig")
	 */
	public function emailCheck() {
		$userManager = $this->get(UserManager::class);
		$tokenGenerator = $this->get('fos_user.util.token_generator');

		$user = $this->getUser();

		if (null === $user->getConfirmationToken()) {
			$user->setConfirmationToken($tokenGenerator->generateToken());
		}

		$userManager->updateUser($user);

		// Email
		$mailerUtils = $this->get(MailerUtils::class);
		$mailerUtils->sendConfirmationEmailMessage($user);

		return array(
			'user' => $user,
		);
	}

	/**
	 * @Route("/email/confirm/{token}", name="core_user_email_confirm")
	 * @Template("Core/User/emailConfirm.html.twig")
	 */
	public function emailConfirm($token) {
	    $om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);

		$invalidToken = false;
		$invalidUser = false;

		$user = $userRepository->findOneByConfirmationToken($token);
		if (null === $user) {
			$invalidToken = true;
		} else if ($this->getUser()->getId() != $user->getId()) {
			$invalidUser = true;
		}

		if (!$invalidToken && !$invalidUser) {

			$user->setConfirmationToken(null);
			$user->setEmailConfirmed(true);

			$om->flush();

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.email_confirmation.confirm.success', array( '%email%' => $user->getEmail() )));

			return $this->redirect($this->generateUrl('core_welcome'));
		}

		return array(
			'invalidToken' => $invalidToken,
			'invalidUser'  => $invalidUser,
		);
	}

	/**
	 * @Route("/email/unsubscribe/{list}/{encryptedEmail}", requirements={"list" = "notifications|weeknews"}, name="core_user_email_unsubscribe")
	 * @Template("Core/User/emailUnsubscribe.html.twig")
	 */
	public function emailUnsubscribe($list, $encryptedEmail) {
        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);

		$invalidEmail = false;

		$email = $this->get(CryptoUtils::class)->decryptString($encryptedEmail);
		$user = $userRepository->findOneByEmail($email);
		if (null === $user) {
			$invalidEmail = true;
		}

		if (!$invalidEmail) {

			switch ($list) {

				case MailerUtils::LIST_NOTIFICATIONS:
					$user->getMeta()->setIncomingMessageEmailNotificationEnabled(false);
					$user->getMeta()->setNewFollowerEmailNotificationEnabled(false);
					$user->getMeta()->setNewLikeEmailNotificationEnabled(false);
					$user->getMeta()->setNewVoteEmailNotificationEnabled(false);
					$user->getMeta()->setNewFollowingPostEmailNotificationEnabled(false);
					$user->getMeta()->setNewWatchActivityEmailNotificationEnabled(false);
					$user->getMeta()->setNewSpotlightEmailNotificationEnabled(false);
					break;

				case MailerUtils::LIST_WEEKNEWS:
					$user->getMeta()->setWeekNewsEmailEnabled(false);
					break;

			}

			$om->flush();

		}

		return array(
			'invalidEmail' => $invalidEmail,
			'email'        => $email,
			'list'         => $list,
		);
	}

	/**
	 * @Route("boiseux/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_user_list_filter")
	 * @Route("boiseux/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("boiseux", name="core_user_list")
	 * @Route("boiseux/{page}", requirements={"page" = "\d+"}, name="core_user_list_page")
	 * @Route("boiseux.geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_user_list_geojson")
	 * @Route("collectifs", defaults={"family"="team"}, name="core_user_team_list")
	 * @Route("collectifs/{page}", defaults={"family"="team"}, requirements={"page" = "\d+"}, name="core_user_team_list_page")
	 * @Route("collectifs.geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson", "family"="team"}, name="core_user_team_list_geojson")
	 * @Template("Core/User/list.html.twig")
	 */
	public function list(Request $request, $page = 0, $layout = 'view', $family = 'user') {
		$searchUtils = $this->get(SearchUtils::class);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'skill':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'meta.skills.label' ));
						$filters[] = $filter;

						break;

					case 'account-type':

						$filter = new \Elastica\Query\MatchPhrase('accountType', $facet->value);
						$filters[] = $filter;

						break;

					case 'around':

						if (isset($facet->value)) {
							$filter = new \Elastica\Query\GeoDistance('geoPoint', $facet->value, '100km');
							$filters[] = $filter;
						}

						break;

					case 'geocoded':

						$filter = new \Elastica\Query\Exists('geoPoint');
						$filters[] = $filter;

						break;

					case 'location':

						$localisableUtils = $this->get(LocalisableUtils::class);
						$boundsAndLocation = $localisableUtils->getBoundsAndLocation($facet->value);

						if (!is_null($boundsAndLocation)) {
							$filter = new \Elastica\Query\BoolQuery();
							if (isset($boundsAndLocation['bounds'])) {
								$geoQuery = new \Elastica\Query\GeoBoundingBox('geoPoint', $boundsAndLocation['bounds']);
								$filter->addShould($geoQuery);
							}
							if (isset($boundsAndLocation['location'])) {
								$geoQuery = new \Elastica\Query\GeoDistance('geoPoint', $boundsAndLocation['location'], '20km');
								$filter->addShould($geoQuery);
							}
							$filters[] = $filter;
						}

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'createdAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-followers':
						$sort = array( 'meta.followerCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'meta.recievedLikeCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-members':
						$sort = array( 'meta.memberCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'displayname', 'fullname', 'username' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) use ($family) {

				$sort = array('meta.contributionCount' => array('order' => 'desc'));

			},
			function(&$filters) use ($family) {
				if ($family == 'team') {
					$filter = new \Elastica\Query\Term([ 'isTeam' => [ 'value' => true, 'boost' => 1.0 ] ]);
					$filters[] = $filter;
				} else {
					$filter = new \Elastica\Query\Term([ 'isTeam' => [ 'value' => false, 'boost' => 1.0 ] ]);
					$filters[] = $filter;
				}
			},
			'core_user',
			\App\Entity\Core\User::CLASS_NAME,
            $family == 'team' ? 'core_user_team_list_page' : 'core_user_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'family' => $family,
			'users'  => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $user) {
				$properties = array(
					'color' => array( 'orange', 'green', 'blue', 'orange', 'green' )[$user->getAccountType()],
					'cardUrl' => $this->generateUrl('core_user_card', array( 'username' => $user->getUsernameCanonical() )),
				);
				$gerometry = new \GeoJson\Geometry\Point($user->getGeoPoint());
				$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
			}
			$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
			$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

			$parameters = array_merge($parameters, array(
				'collection' => $collection,
			));

			return $this->render('Core/User/list-xhr.geojson.twig', $parameters);
		}

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/User/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/parametres", name="core_user_settings_me_old")
	 * @Route("/@me/parametres", name="core_user_settings_me")
	 */
	public function oldSettings(Request $request, $username = null) {
		$username = $this->getUser()->getUsernameCanonical();

		return $this->redirect($this->generateUrl('core_user_settings', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/parametres", name="core_user_settings")
	 * @Template("Core/User/settings.html.twig")
	 */
	public function settings(Request $request, $username) {
		$om = $this->getDoctrine()->getManager();

		$user = $this->retrieveUserByUsername($username);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			if ($user->getIsTeam()) {
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamAndUser($user, $this->getUser())) {
					throw $this->createNotFoundException('Access denied');
				}
			} else if ($user != $this->getUser()) {
				throw $this->createNotFoundException('Access denied (core_user_settings)');
			}
		}

		$oldUsername = $user->getUsernameCanonical();
		$form = $this->createForm($user->getIsTeam() ? UserTeamSettingsType::class : UserSettingsType::class, $user);

		if ($request->isMethod('post')) {
			$form->handleRequest($request);

			if ($form->isValid()) {

				// Check if new username
				if ($user->getUsernameCanonical() != $oldUsername) {

					// Check if witness already exists
					$userWitnessRepository = $om->getRepository(UserWitness::class);
					$userWitness = $userWitnessRepository->findOneByUsername($oldUsername);
					if (is_null($userWitness)) {

						// No previous, create a new witness
						$userWitness = new UserWitness();
						$userWitness->setUsername($oldUsername);
						$userWitness->setUser($user);

						$om->persist($userWitness);

					}

				}

				$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
				$fieldPreprocessorUtils->preprocessFields($user->getMeta()->getBiography());

				// Geocode location
				$localisableUtils = $this->get(LocalisableUtils::class);
				$localisableUtils->geocodeLocation($user);

				// Default avatar
				if (is_null($user->getAvatar())) {
					$userUtils = $this->get(UserUtils::class);
					$userUtils->createDefaultAvatar($user);
				}

				// Final update of user entity
                // TODO : Process empty display name
                $om->flush();

				// Search index update
				$searchUtils = $this->get(SearchUtils::class);
				$searchUtils->replaceEntityInIndex($user);

				// Flashbag
				$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.form.alert.settings_success'));

				return $this->redirect($this->generateUrl('core_user_show', array('username' => $user->getUsernameCanonical())));
			}

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		return array(
			'user' => $user,
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/@me/counters.json", name="core_user_counters_me", defaults={"_format" = "json"})
	 * @Template("Core/User/counters-xhr.json.twig")
	 */
	public function counters(Request $request) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_user_counters_me)');
		}

		$user = $this->getUser();
		if (is_null($user)) {
			throw $this->createNotFoundException('No current user (core_user_counters_me)');
		}
		$meta = $user->getMeta();

		$listedCounterKey = null;
		$listedCounterValue = null;

		// Check if a listed entity type is provided (to retrieve counter before computing a new one)
		$listedEntityType = $request->get('listed_entity_type', false);
		if ($listedEntityType) {

			$typableUtils = $this->get(TypableUtils::class);
			$listedEntityStrippedName = $typableUtils->getStrippedNameByType($listedEntityType);

			$propertyPath = 'unlisted_'.$listedEntityStrippedName.'_count';
			$propertyUtils = $this->get(PropertyUtils::class);

			try {

				// Retrieve counter value
				$listedCounterKey = $propertyUtils->camelCasePropertyAccessor('', $propertyPath);
				$listedCounterValue = $propertyUtils->getValue($meta, $propertyPath);

			} catch (\Exception $e) {}

		}

		// Compute unlisted counters
		$userUtils = $this->container->get(UserUtils::class);
		$userUtils->computeUnlistedCounters($user, $listedEntityType);

		$counters = array(
			'unlistedWonderCreationCount' => $meta->getUnlistedWonderCreationCount(),
			'unlistedWonderPlanCount' => $meta->getUnlistedWonderPlanCount(),
			'unlistedWonderWorkshopCount' => $meta->getUnlistedWonderWorkshopCount(),
			'unlistedFindFindCount' => $meta->getUnlistedFindFindCount(),
			'unlistedHowtoHowtoCount' => $meta->getUnlistedHowtoHowtoCount(),
			'unlistedKnowledgeWoodCount' => $meta->getUnlistedKnowledgeWoodCount(),
			'unlistedKnowledgeProviderCount' => $meta->getUnlistedKnowledgeProviderCount(),
			'unlistedKnowledgeSchoolCount' => $meta->getUnlistedKnowledgeSchoolCount(),
			'unlistedKnowledgeBookCount' => $meta->getUnlistedKnowledgeBookCount(),
			'unlistedKnowledgeSoftwareCount' => $meta->getUnlistedKnowledgeSoftwareCount(),
			'unlistedBlogPostCount' => $meta->getUnlistedBlogPostCount(),
			'unlistedFaqQuestionCount' => $meta->getUnlistedFaqQuestionCount(),
			'unlistedQaQuestionCount' => $meta->getUnlistedQaQuestionCount(),
			'unlistedPromotionGraphicCount' => $meta->getUnlistedPromotionGraphicCount(),
			'unlistedWorkflowWorkflowCount' => $meta->getUnlistedWorkflowWorkflowCount(),
			'unlistedCollectionCollectionCount' => $meta->getUnlistedCollectionCollectionCount(),
			'unlistedOfferOfferCount' => $meta->getUnlistedOfferOfferCount(),
			'unlistedEventEventCount' => $meta->getUnlistedEventEventCount(),
		);

		if (!is_null($listedCounterKey) && !is_null($listedCounterValue)) {
			$counters[$listedCounterKey] = $listedCounterValue;
		}

		return array(
			'counters' => $counters,
		);
	}

	/**
	 * @Route("/@{username}/location.geojson", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_location", defaults={"_format" = "json"})
	 * @Template("Core/User/location.geojson.twig")
	 */
	public function location(Request $request, $username) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_location', array( 'username' => $user->getUsernameCanonical() )));
		}

		$features = array();
		if (!is_null($user->getLongitude()) && !is_null($user->getLatitude())) {
			$properties = array(
				'color' => array( 'orange', 'green', 'blue', 'orange', 'green' )[$user->getAccountType()],
				'cardUrl' => $this->generateUrl('core_user_card', array( 'username' => $user->getUsername() )),
			);
			$gerometry = new \GeoJson\Geometry\Point($user->getGeoPoint());
			$features[] = new \GeoJson\Feature\Feature($gerometry, $properties);
		}

		$crs = new \GeoJson\CoordinateReferenceSystem\Named('urn:ogc:def:crs:OGC:1.3:CRS84');
		$collection = new \GeoJson\Feature\FeatureCollection($features, $crs);

		return array(
			'collection' => $collection,
		);
	}

	/**
	 * @Route("/@{username}/card.xhr", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_card")
	 * @Template("Core/User/card-xhr.html.twig")
	 */
	public function card(Request $request, $username) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed (core_user_card)');
		}

		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_card', array( 'username' => $user->getUsernameCanonical() )));
		}

		return array(
			'user' => $user,
		);
	}

	/**
	 * @Route("/{username}/a-propos", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_about_old")
	 */
	public function oldShowAbout($username) {
		return $this->redirect($this->generateUrl('core_user_show_about', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/a-propos", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_about")
	 * @Template("Core/User/showAbout.html.twig")
	 */
	public function showAbout($username) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_about', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();

		$testimonialRepository = $om->getRepository(Testimonial::CLASS_NAME);
		$testimonials = $testimonialRepository->findByUser($user);

		return $this->_fillCommonShowParameters($user, array(
			'tab'             => 'about',
			'hasMap'          => !is_null($user->getLatitude()) && !is_null($user->getLongitude()),
			'testimonials'    => $testimonials,
		));
	}

	/**
	 * @Route("/{username}/coups-de-coeur", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_likes_old")
	 */
	public function oldShowLikes($username) {
		return $this->redirect($this->generateUrl('core_user_show_likes', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/coups-de-coeur", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_likes")
	 * @Route("/@{username}/coups-de-coeur/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "sent|recieved"}, name="core_user_show_likes_filter")
	 * @Route("/@{username}/coups-de-coeur/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_likes_filter_page")
	 * @Template("Core/User/showLikes.html.twig")
	 */
	public function showLikes(Request $request, $username, $filter = "sent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_likes', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $likeRepository->findPaginedByUser($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_likes_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $filter == 'recieved' ? $user->getMeta()->getRecievedLikeCount() : $user->getMeta()->getSentLikeCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Like/list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'likes',
		)));
	}

	/**
	 * @Route("/{username}/commentaires", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_comments_old")
	 */
	public function oldShowComments($username) {
		return $this->redirect($this->generateUrl('core_user_show_comments', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/commentaires", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_comments")
	 * @Route("/@{username}/commentaires/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "page" = "\d+"}, name="core_user_show_comments_page")
	 * @Template("Core/User/showComments.html.twig")
	 */
	public function showComments(Request $request, $username, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_comments', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $commentRepository->findPaginedByUserGroupByEntityType($user, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_comments_page', array( 'username' => $user->getUsernameCanonical() ), $page, $user->getMeta()->getCommentCount());

		$parameters = array(
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Comment/list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'comments',
		)));
	}

	/**
	 * @Route("/{username}/votes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_votes_old")
	 */
	public function oldShowVotes($username) {
		return $this->redirect($this->generateUrl('core_user_show_votes', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/votes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_votes")
	 * @Route("/@{username}/votes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "positive|negative"}, name="core_user_show_votes_filter")
	 * @Route("/@{username}/votes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "positive|negative", "page" = "\d+"}, name="core_user_show_votes_filter_page")
	 * @Template("Core/User/showVotes.html.twig")
	 */
	public function showVotes(Request $request, $username, $filter = 'positive', $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_votes', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $voteRepository->findPaginedByUserGroupByEntityType($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_votes_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $filter == 'up' ? $user->getMeta()->getPositiveVoteCount() : $user->getMeta()->getNegativeVoteCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Vote/list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'votes',
		)));
	}

	/**
	 * @Route("/{username}/reviews", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_reviews_old")
	 */
	public function oldShowReviews($username) {
		return $this->redirect($this->generateUrl('core_user_show_reviews', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/reviews", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_reviews")
	 * @Route("/@{username}/reviews/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_reviews_filter")
	 * @Route("/@{username}/reviews/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_reviews_filter_page")
	 * @Template("Core/User/showReviews.html.twig")
	 */
	public function showReviews(Request $request, $username, $filter = 'recent', $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_reviews', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $reviewRepository->findPaginedByUserGroupByEntityType($user, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_reviews_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $user->getMeta()->getReviewCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Review/list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'reviews',
		)));
	}

	/**
	 * @Route("/{username}/feedbacks", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_feedbacks_old")
	 */
	public function oldShowFeedbacks($username) {
		return $this->redirect($this->generateUrl('core_user_show_feedbacks', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/feedbacks", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_feedbacks")
	 * @Route("/@{username}/feedbacks/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_feedbacks_filter")
	 * @Route("/@{username}/feedbacks/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_feedbacks_filter_page")
	 * @Template("Core/User/showFeedbacks.html.twig")
	 */
	public function showFeedbacks(Request $request, $username, $filter = "recent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_feedbacks', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $feedbackRepository->findPaginedByUserGroupByEntityType($user, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_feedbacks_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $user->getMeta()->getFeedbackCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Feedback/list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'feedbacks',
		)));
	}

	/**
	 * @Route("/{username}/creations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_creations_old")
	 */
	public function oldShowCreations($username) {
		return $this->redirect($this->generateUrl('core_user_show_creations', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/creations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_creations")
	 * @Route("/@{username}/creations/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_creations_filter")
	 * @Route("/@{username}/creations/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_creations_filter_page")
	 * @Template("Core/User/showCreations.html.twig")
	 */
	public function showCreations(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_creations', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Creations

		$om = $this->getDoctrine()->getManager();
		$creationRepository = $om->getRepository(Creation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $creationRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_creations_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'creations'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Creation/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'creations',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/ateliers", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workshops_old")
	 */
	public function oldShowWorkshops($username) {
		return $this->redirect($this->generateUrl('core_user_show_workshops', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/ateliers", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workshops")
	 * @Route("/@{username}/ateliers/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_workshops_filter")
	 * @Route("/@{username}/ateliers/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_workshops_filter_page")
	 * @Template("Core/User/showWorkshops.html.twig")
	 */
	public function showWorkshops(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_workshops', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Workshops

		$om = $this->getDoctrine()->getManager();
		$workshopRepository = $om->getRepository(Workshop::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workshopRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_workshops_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workshops'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Workshop/list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'workshops',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/plans", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_plans_old")
	 */
	public function oldShowPlans($username) {
		return $this->redirect($this->generateUrl('core_user_show_plans', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/plans", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_plans")
	 * @Route("/@{username}/plans/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_plans_filter")
	 * @Route("/@{username}/plans/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_plans_filter_page")
	 * @Template("Core/User/showPlans.html.twig")
	 */
	public function showPlans(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_plans', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Plans

		$om = $this->getDoctrine()->getManager();
		$planRepository = $om->getRepository(Plan::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $planRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_plans_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'plans'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Wonder/Plan/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'plans',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/pas-a-pas", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_howtos_old")
	 */
	public function showOldHowtos($username) {
		return $this->redirect($this->generateUrl('core_user_show_howtos', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/pas-a-pas", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_howtos")
	 * @Route("/@{username}/pas-a-pas/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_howtos_filter")
	 * @Route("/@{username}/pas-a-pas/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_howtos_filter_page")
	 * @Template("Core/User/showHowtos.html.twig")
	 */
	public function showHowtos(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_howtos', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Howtos

		$om = $this->getDoctrine()->getManager();
		$howtoRepository = $om->getRepository(Howto::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $howtoRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_howtos_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'howtos'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Howto/Howto/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'howtos',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/trouvailles", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_finds_old")
	 */
	public function showOldFinds($username) {
		return $this->redirect($this->generateUrl('core_user_show_finds', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/trouvailles", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_finds")
	 * @Route("/@{username}/trouvailles/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_finds_filter")
	 * @Route("/@{username}/trouvailles/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_finds_filter_page")
	 * @Template("Core/User/showFinds.html.twig")
	 */
	public function showFinds(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_finds', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Finds

		$om = $this->getDoctrine()->getManager();
		$findRepository = $om->getRepository(Find::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $findRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_finds_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'finds'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Find/Find/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'finds',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/questions", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_questions_old")
	 */
	public function showOldQuestions($username) {
		return $this->redirect($this->generateUrl('core_user_show_questions', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/questions", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_questions")
	 * @Route("/@{username}/questions/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_questions_filter")
	 * @Route("/@{username}/questions/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_questions_filter_page")
	 * @Template("Core/User/showQuestions.html.twig")
	 */
	public function showQuestions(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_questions', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Questions

		$om = $this->getDoctrine()->getManager();
		$questionRepository = $om->getRepository(Question::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $questionRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_questions_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'questions'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Qa/Question/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'questions',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/reponses", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_answers_old")
	 */
	public function showOldAnswers($username) {
		return $this->redirect($this->generateUrl('core_user_show_answers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/reponses", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_answers")
	 * @Route("/@{username}/reponses/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_answers_filter")
	 * @Route("/@{username}/reponses/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_answers_filter_page")
	 * @Template("Core/User/showAnswers.html.twig")
	 */
	public function showAnswers(Request $request, $username, $filter = "recent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_answers', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $answerRepository->findPaginedByUser($user, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_answers_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $user->getMeta()->getAnswerCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Qa/Answer/list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'answers',
		)));
	}

	/**
	 * @Route("/{username}/graphismes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_graphics_old")
	 */
	public function showOldGraphics($username) {
		return $this->redirect($this->generateUrl('core_user_show_graphics', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/graphismes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_graphics")
	 * @Route("/@{username}/graphismes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_graphics_filter")
	 * @Route("/@{username}/graphismes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_graphics_filter_page")
	 * @Template("Core/User/showGraphics.html.twig")
	 */
	public function showGraphics(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_graphics', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Graphics

		$om = $this->getDoctrine()->getManager();
		$graphicRepository = $om->getRepository(Graphic::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $graphicRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_graphics_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'graphics'    => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Promotion/Graphic/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'graphics',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/processus", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workflows_old")
	 */
	public function showOldWorkflows($username) {
		return $this->redirect($this->generateUrl('core_user_show_workflows', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/processus", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workflows")
	 * @Route("/@{username}/processus/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_workflows_filter")
	 * @Route("/@{username}/processus/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_workflows_filter_page")
	 * @Template("Core/User/showWorkflows.html.twig")
	 */
	public function showWorkflows(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_workflows', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Workflows

		$om = $this->getDoctrine()->getManager();
		$workflowRepository = $om->getRepository(Workflow::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $workflowRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_workflows_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'workflows'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Workflow:list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'workflows',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/annonces", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_offers_old")
	 */
	public function showOldOffers($username) {
		return $this->redirect($this->generateUrl('core_user_show_offers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/annonces", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_offers")
	 * @Route("/@{username}/annonces/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_offers_filter")
	 * @Route("/@{username}/annonces/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_offers_filter_page")
	 * @Template("Core/User/showOffers.html.twig")
	 */
	public function showOffers(Request $request, $username, $filter = null, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_offers', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);

		// Default filter

		if (is_null($filter)) {
			if ($isGrantedOwner) {
				$filter = 'recent';
			} else {
				$filter = 'popular-likes';
			}
		}

		// Offers

		$om = $this->getDoctrine()->getManager();
		$offerRepository = $om->getRepository(Offer::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $offerRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || $isGrantedOwner);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_offers_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'offers'      => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Offer/Offer/list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'offers',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/abonnements", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_following_old")
	 */
	public function showOldFollowing($username) {
		return $this->redirect($this->generateUrl('core_user_show_following', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/abonnements", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_following")
	 * @Route("/@{username}/abonnements/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_following_filter")
	 * @Route("/@{username}/abonnements/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_following_filter_page")
	 * @Template("Core/User/showFollowing.html.twig")
	 */
	public function showFollowing(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_following', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Following

		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $followerRepository->findPaginedByUser($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_following_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'followers'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Follower/following-list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'following',
		)));
	}

	/**
	 * @Route("/{username}/abonnes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_followers_old")
	 */
	public function showOldFollowers($username) {
		return $this->redirect($this->generateUrl('core_user_show_followers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/abonnes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_followers")
	 * @Route("/@{username}/abonnes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_followers_filter")
	 * @Route("/@{username}/abonnes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_followers_filter_page")
	 * @Template("Core/User/showFollowers.html.twig")
	 */
	public function showFollowers(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_followers', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Followers

		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $followerRepository->findPaginedByFollowingUser($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_followers_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'followers'   => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Follower/followers-list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::class);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'followers'       => $paginator,
			'tab'             => 'followers',
		)));
	}

	/**
	 * @Route("/@{username}/membres", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_members")
	 * @Route("/@{username}/membres/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_members_filter")
	 * @Route("/@{username}/membres/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_members_filter_page")
	 * @Template("Core/User/showMembers.html.twig")
	 */
	public function showMembers(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_members', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Member

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $memberRepository->findPaginedByTeam($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_members_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'members'     => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Member/members-list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'members',
		)));
	}

	/**
	 * @Route("/@{username}/invitations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_invitations")
	 * @Route("/@{username}/invitations/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_invitations_filter")
	 * @Route("/@{username}/invitations/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_invitations_filter_page")
	 * @Template("Core/User/showInvitations.html.twig")
	 */
	public function showInvitations(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_invitations', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !$isGrantedOwner) {
			return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Member

		$om = $this->getDoctrine()->getManager();
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		if ($user->getIsTeam()) {
			$paginator = $memberInvitationRepository->findPaginedByTeam($user, $offset, $limit, $filter);
		} else {
			$paginator = $memberInvitationRepository->findPaginedByRecipient($user, $offset, $limit, $filter);
		}
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_invitations_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'invitations' => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Member/invitations-list-xhr.html.twig', array_merge(array( 'user' => $user ), $parameters));
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'invitations',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/@{username}/demandes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_requests")
	 * @Route("/@{username}/demandes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_requests_filter")
	 * @Route("/@{username}/demandes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_requests_filter_page")
	 * @Template("Core/User/showRequests.html.twig")
	 */
	public function showRequests(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_requests', array( 'username' => $user->getUsernameCanonical() )));
		}

		$isGrantedOwner = $this->_isGrantedOwner($user);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && !$isGrantedOwner) {
			return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Member

		$om = $this->getDoctrine()->getManager();
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		if ($user->getIsTeam()) {
			$paginator = $memberRequestRepository->findPaginedByTeam($user, $offset, $limit, $filter);
		} else {
			$paginator = $memberRequestRepository->findPaginedBySender($user, $offset, $limit, $filter);
		}
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_requests_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'requests'    => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Member/requests-list-xhr.html.twig', array_merge(array( 'user' => $user ), $parameters));
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'requests',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/@{username}/collectifs", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_teams")
	 * @Route("/@{username}/collectifs/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_teams_filter")
	 * @Route("/@{username}/collectifs/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_teams_filter_page")
	 * @Template("Core/User/showTeams.html.twig")
	 */
	public function showTeams(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_teams', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Teams

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::class);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $memberRepository->findPaginedByUser($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_teams_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'members'     => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('Core/Follower/teams-list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'teams',
		)));
	}

	/**
	 * @Route("/@me", name="core_user_show_me")
	 */
	public function showMe() {
		$username = $this->getUser()->getUsernameCanonical();

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show")
	 */
	public function show($username) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $user->getUsernameCanonical() )));
		}

		$CrawlerDetect = new CrawlerDetect();
		if ($CrawlerDetect->isCrawler() || $user->getIsTeam() && !is_null($user->getMeta()->getBiography()) && !empty($user->getMeta()->getBiography()->getHtmlBody())) {	 /* Return about page for Crawlers */
			$forwardController = 'App\Controller\Core\UserController::showAbout';
		} else if ($user->getIsTeam()) {
			$forwardController = 'App\Controller\Core\UserController::showMembers';
		} else if ($user->getMeta()->getPublicCreationCount() > 0) {
			$forwardController = 'App\Controller\Core\UserController::showCreations';
		} else if ($user->getMeta()->getPublicPlanCount() > 0) {
			$forwardController = 'App\Controller\Core\UserController::showPlans';
		} else if ($user->getMeta()->getPublicHowtoCount() > 0) {
			$forwardController = 'App\Controller\Core\UserController::showHowtos';
		} else if ($user->getMeta()->getPublicWorkshopCount() > 0) {
			$forwardController = 'App\Controller\Core\UserController::showWorkshops';
		} else if ($user->getMeta()->getPublicFindCount() > 0) {
			$forwardController = 'App\Controller\Core\UserController::showFinds';
		} else {
			$forwardController = 'App\Controller\Core\UserController::showAbout';
		}

		if ($user->getIsTeam() && $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$om = $this->getDoctrine()->getManager();
			$memberRepository = $om->getRepository(Member::CLASS_NAME);
			$isMember = $memberRepository->existsByTeamAndUser($user, $this->getUser());

		} else {
			$isMember = false;
		}

		$response = $this->forward($forwardController, array(
			'username' => $username,
			'isMember' => $isMember,
		));
		return $response;
	}

	// Team /////

	/**
	 * @Route("collectifs/new", name="core_user_team_new")
	 * @Template("Core/User/Team/new.html.twig")
	 */
	public function teamNew() {
		if (!$this->getUser()->getEmailConfirmed()) {
			throw new NotFoundException('Not allowed (core_user_team_new)');
		}

		$team = new User();
		$form = $this->createForm(UserTeamType::class, $team);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("collectifs/create", methods={"POST"}, name="core_user_team_create")
	 * @Template("Core/User/Team/new.html.twig")
	 */
	public function teamCreate(Request $request, UserManager $userManager) {
		if (!$this->getUser()->getEmailConfirmed()) {
			throw new NotFoundException('Not allowed (core_user_team_create)');
		}

		$this->createLock('core_user_team_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$team = new User();
		$team->setEmail(uniqid('', true).'-team@lairdubois.fr');		// Fake email - to bypass Registration validation on this field
		$team->setPlainPassword(bin2hex(random_bytes(20)));								// Put a random password - to bypass Registration validation on this field AND avoid logon on this account
		$form = $this->createForm(UserTeamType::class, $team);
		$form->handleRequest($request);

		if ($form->isValid()) {

            $team->setIsTeam(true);
            $team->getMeta()->setRequestEnabled(true);

		    $userManager->createFromEntity($team, array( 'ROLE_TEAM' ));

			// Add team's creator as first member
			$memberManager = $this->get(MemberManager::class);
			$memberManager->create($team, $this->getUser());

			$om->flush();

			// Search index update
			$searchUtils = $this->get(SearchUtils::class);
			$searchUtils->insertEntityToIndex($team);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.form.alert.team_success', array( '%displayname%' => $team->getDisplayname() )));

			/////

			$registration = new Registration();
			$registration->setCreator($this->getUser());
			$registration->setUser($team);
			$registration->setClientIp4($request->getClientIp());
			$registration->setClientUserAgent($request->server->get('HTTP_USER_AGENT'));

			$om->persist($registration);
			$om->flush();

			// Send admin email notification
			$mailerUtils = $this->container->get(MailerUtils::class);
			$mailerUtils->sendNewTeamNotificationEmailMessage($this->getUser(), $team);

			return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $team->getUsernameCanonical()) ));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		return array(
			'user' => $team,
			'form' => $form->createView(),
		);
	}

	// Admin /////

	/**
	 * @Route("/@{username}/admin", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_admin")
	 * @Template("Core/User/showAdmin.html.twig")
	 */
	public function showAdmin($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$om = $this->getDoctrine()->getManager();
		$registrationRepository = $om->getRepository(Registration::CLASS_NAME);
		$registration = $registrationRepository->findOneByUser($user);

		return $this->_fillCommonShowParameters($user, array(
			'registration' => $registration,
			'tab'          => 'admin',
		));
	}

	/**
	 * @Route("/@{username}/admin/converttoteam", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_converttoteam")
	 */
	public function adminConvertToTeam($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		// Remove all pending invitations
		$memberInvitationManager = $this->get(MemberInvitationManager::class);
		$memberInvitationRepository = $om->getRepository(MemberInvitation::CLASS_NAME);
		$memberInvitations = $memberInvitationRepository->findPaginedByRecipient($user);
		foreach ($memberInvitations as $memberInvitation) {
			$memberInvitationManager->delete($memberInvitation);
		}

		// Remove all pending requests
		$memberRequestManager = $this->get(MemberRequestManager::class);
		$memberRequestRepository = $om->getRepository(MemberRequest::CLASS_NAME);
		$memberRequests = $memberRequestRepository->findPaginedBySender($user);
		foreach ($memberRequests as $memberRequest) {
			$memberRequestManager->delete($memberRequest);
		}

		// Remove all members
		$memberManager = $this->get(MemberManager::class);
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$members = $memberRepository->findPaginedByUser($user);
		foreach ($members as $member) {
			$memberManager->delete($member);
		}

		// Convert
		$user->setIsTeam(true);
		$user->setEmail(uniqid('', true).'-team@lairdubois.fr');		// Fake email
		$user->setEmailCanonical($user->getEmail());
		$user->setPlainPassword(bin2hex(random_bytes(20)));									// Put a random password - to avoid logon on this account
		$user->getMeta()->incrementMemberCount($user->getMeta()->getMemberCount());
		$user->getMeta()->setRequestEnabled(true);

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->insertEntityToIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.converttoteam_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/activate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_activate")
	 */
	public function adminActivate($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

        $om = $this->getDoctrine()->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$manipulator = $this->get('fos_user.util.user_manipulator');

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$manipulator->activate($username);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->insertEntityToIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.activate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/deactivate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_deactivate")
	 */
	public function adminDeactivate($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);
		$manipulator = $this->get('fos_user.util.user_manipulator');

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$manipulator->deactivate($username);

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->deleteEntityFromIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.deactivate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/empty", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_empty")
	 */
	public function adminEmpty($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		// Remove likes
		$likableUtils = $this->get(LikableUtils::class);
		$likableUtils->deleteLikesByUser($user, false);

		// Remove followers / followings
		$followerUtils = $this->get(FollowerUtils::class);
		$followerUtils->deleteFollowersByUser($user, false);
		$followerUtils->deleteFollowingsByUser($user, false);

		// TODO

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->replaceEntityInIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.empty_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/delete", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_delete")
	 */
	public function adminDelete($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

        $om = $this->getDoctrine()->getManager();
        $userRepository = $om->getRepository(User::CLASS_NAME);

		$user = $userRepository->findOneByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$avatar = $user->getAvatar();
		$banner = $user->getMeta()->getBanner();

		$user->setAvatar(null);
		$user->getMeta()->setBanner(null);

		if (!is_null($avatar)) {
			$om->remove($avatar);
		}
		if (!is_null($banner)) {
			$om->remove($banner);
		}
		$om->flush();

		$om->remove($user);
		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::class);
		$searchUtils->deleteEntityFromIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.delete_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_welcome'));
	}

}