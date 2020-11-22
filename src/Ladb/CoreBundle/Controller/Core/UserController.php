<?php

namespace Ladb\CoreBundle\Controller\Core;

use FOS\UserBundle\Model\UserInterface;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Controller\UserControllerTrait;
use Ladb\CoreBundle\Entity\Core\Feedback;
use Ladb\CoreBundle\Entity\Core\Member;
use Ladb\CoreBundle\Entity\Core\MemberInvitation;
use Ladb\CoreBundle\Entity\Core\Review;
use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\Vote;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Form\Type\Core\UserTeamSettingsType;
use Ladb\CoreBundle\Form\Type\Core\UserTeamType;
use Ladb\CoreBundle\Utils\MemberUtils;
use Ladb\CoreBundle\Utils\PropertyUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Symfony\Component\Debug\Exception\UndefinedMethodException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\UserWitness;
use Ladb\CoreBundle\Entity\Knowledge\School\Testimonial;
use Ladb\CoreBundle\Entity\Promotion\Graphic;
use Ladb\CoreBundle\Entity\Qa\Answer;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Core\Follower;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Core\Like;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Entity\Core\Registration;
use Ladb\CoreBundle\Form\Type\Core\UserSettingsType;
use Ladb\CoreBundle\Utils\CryptoUtils;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\UserUtils;

/**
 * @Route("/")
 */
class UserController extends AbstractController {

	use UserControllerTrait;

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

	private function _fillCommonShowParameters(User $user, $parameters, $isGrantedOwner = null) {

		if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

			$invitation = $this->_getInvitation($user, $this->getUser());
			if (!is_null($invitation)) {

				// Flashbag
				$this->get('session')->getFlashBag()->add('info', $this->get('templating')->render('LadbCoreBundle:Core/Member:_invitation-alert.part.html.twig', array(
					'invitation' => $invitation,
				)));

			}

		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'isGrantedOwner'  => is_null($isGrantedOwner) ? $this->_isGrantedOwner($user) : $isGrantedOwner,
			'invitation'      => $this->_getInvitation($user),
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/////

	/**
	 * @Route("/email/check", name="core_user_email_check")
	 * @Template("LadbCoreBundle:Core/User:emailCheck.html.twig")
	 */
	public function emailCheckAction() {
		$userManager = $this->container->get('fos_user.user_manager');
		$tokenGenerator = $this->get('fos_user.util.token_generator');

		$user = $this->getUser();

		if (null === $user->getConfirmationToken()) {
			$user->setConfirmationToken($tokenGenerator->generateToken());
		}

		$userManager->updateUser($user);

		// Email
		$mailerUtils = $this->get(MailerUtils::NAME);
		$mailerUtils->sendConfirmationEmailMessage($user);

		return array(
			'user' => $user,
		);
	}

	/**
	 * @Route("/email/confirm/{token}", name="core_user_email_confirm")
	 * @Template("LadbCoreBundle:Core/User:emailConfirm.html.twig")
	 */
	public function emailConfirmAction($token) {
		$userManager = $this->container->get('fos_user.user_manager');

		$invalidToken = false;
		$invalidUser = false;

		$user = $userManager->findUserByConfirmationToken($token);
		if (null === $user) {
			$invalidToken = true;
		} else if ($this->getUser()->getId() != $user->getId()) {
			$invalidUser = true;
		}

		if (!$invalidToken && !$invalidUser) {

			$user->setConfirmationToken(null);
			$user->setEmailConfirmed(true);

			$userManager->updateUser($user);

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
	 * @Template("LadbCoreBundle:Core/User:emailUnsubscribe.html.twig")
	 */
	public function emailUnsubscribeAction($list, $encryptedEmail) {
		$userManager = $this->container->get('fos_user.user_manager');

		$invalidEmail = false;

		$email = $this->get(CryptoUtils::NAME)->decryptString($encryptedEmail);
		$user = $userManager->findUserByEmail($email);
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

			$userManager->updateUser($user);

		}

		return array(
			'invalidEmail' => $invalidEmail,
			'email'        => $email,
			'list'         => $list,
		);
	}

	/**
	 * @Route("/boiseux/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_user_list_filter")
	 * @Route("/boiseux/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_list_filter_page")
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/boiseux", name="core_user_list")
	 * @Route("/boiseux/{page}", requirements={"page" = "\d+"}, name="core_user_list_page")
	 * @Route("/boiseux.geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_user_list_geojson")
	 * @Route("/collectifs", defaults={"family"="team"}, name="core_user_team_list")
	 * @Route("/collectifs/{page}", defaults={"family"="team"}, requirements={"page" = "\d+"}, name="core_userteam_list_page_")
	 * @Route("/collectifs.geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson", "family"="team"}, name="core_user_team_list_geojson")
	 * @Template("LadbCoreBundle:Core/User:list.html.twig")
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view', $family = 'user') {
		$searchUtils = $this->get(SearchUtils::NAME);

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

						$localisableUtils = $this->get(LocalisableUtils::NAME);
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
			function(&$filters, &$sort) {

				$sort = array( 'meta.recievedLikeCount' => array( 'order' => 'desc' ) );

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
			'fos_elastica.index.ladb.core_user',
			\Ladb\CoreBundle\Entity\Core\User::CLASS_NAME,
			$family == 'team' ? 'core_team_list_page' : 'core_user_list_page'
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

			return $this->render('LadbCoreBundle:Core/User:list-xhr.geojson.twig', $parameters);
		}

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/User:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/parametres", name="core_user_settings_me_old")
	 * @Route("/@me/parametres", name="core_user_settings_me")
	 */
	public function oldSettingsAction(Request $request, $username = null) {
		$username = $this->getUser()->getUsernameCanonical();

		return $this->redirect($this->generateUrl('core_user_settings', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/parametres", name="core_user_settings")
	 * @Template("LadbCoreBundle:Core/User:settings.html.twig")
	 */
	public function settingsAction(Request $request, $username) {
		$om = $this->getDoctrine()->getManager();

		$user = $this->retrieveUserByUsername($username);
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			if ($user->getIsTeam()) {
				$memberRepository = $om->getRepository(Member::class);
				if (!$memberRepository->existsByTeamAndUser($user, $this->getUser())) {
					throw $this->createNotFoundException('Access denied');
				}
			} else if ($user != $this->getUser()) {
				throw $this->createNotFoundException('Access denied');
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

				$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
				$fieldPreprocessorUtils->preprocessFields($user->getMeta()->getBiography());

				// Geocode location
				$localisableUtils = $this->get(LocalisableUtils::NAME);
				$localisableUtils->geocodeLocation($user);

				// Default avatar
				if (is_null($user->getAvatar())) {
					$userUtils = $this->get(UserUtils::NAME);
					$userUtils->createDefaultAvatar($user);
				}

				$om->flush();

				// Search index update
				$searchUtils = $this->get(SearchUtils::NAME);
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
	 * @Template("LadbCoreBundle:Core/User:counters-xhr.json.twig")
	 */
	public function countersAction(Request $request) {
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

			$typableUtils = $this->get(TypableUtils::NAME);
			$listedEntityStrippedName = $typableUtils->getStrippedNameByType($listedEntityType);

			$propertyPath = 'unlisted_'.$listedEntityStrippedName.'_count';
			$propertyUtils = $this->get(PropertyUtils::NAME);

			try {

				// Retrieve counter value
				$listedCounterKey = $propertyUtils->camelCasePropertyAccessor('', $propertyPath);
				$listedCounterValue = $propertyUtils->getValue($meta, $propertyPath);

			} catch (\Exception $e) {}

		}

		// Compute unlisted counters
		$userUtils = $this->container->get(UserUtils::NAME);
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
	 * @Template("LadbCoreBundle:Core/User:location.geojson.twig")
	 */
	public function locationAction(Request $request, $username) {
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
	 * @Template("LadbCoreBundle:Core/User:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $username) {
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
	public function oldShowAboutAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_about', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/a-propos", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_about")
	 * @Template("LadbCoreBundle:Core/User:showAbout.html.twig")
	 */
	public function showAboutAction($username) {
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
	public function oldShowLikesAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_likes', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/coups-de-coeur", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_likes")
	 * @Route("/@{username}/coups-de-coeur/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "sent|recieved"}, name="core_user_show_likes_filter")
	 * @Route("/@{username}/coups-de-coeur/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_likes_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showLikes.html.twig")
	 */
	public function showLikesAction(Request $request, $username, $filter = "sent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_likes', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Like:list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/commentaires", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_comments_old")
	 */
	public function oldShowCommentsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_comments', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/commentaires", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_comments")
	 * @Route("/@{username}/commentaires/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "page" = "\d+"}, name="core_user_show_comments_page")
	 * @Template("LadbCoreBundle:Core/User:showComments.html.twig")
	 */
	public function showCommentsAction(Request $request, $username, $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_comments', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Comment:list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/votes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_votes_old")
	 */
	public function oldShowVotesAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_votes', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/votes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_votes")
	 * @Route("/@{username}/votes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "positive|negative"}, name="core_user_show_votes_filter")
	 * @Route("/@{username}/votes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "positive|negative", "page" = "\d+"}, name="core_user_show_votes_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showVotes.html.twig")
	 */
	public function showVotesAction(Request $request, $username, $filter = 'positive', $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_votes', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$voteRepository = $om->getRepository(Vote::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Vote:list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/reviews", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_reviews_old")
	 */
	public function oldShowReviewsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_reviews', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/reviews", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_reviews")
	 * @Route("/@{username}/reviews/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_reviews_filter")
	 * @Route("/@{username}/reviews/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_reviews_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showReviews.html.twig")
	 */
	public function showReviewsAction(Request $request, $username, $filter = 'recent', $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_reviews', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$reviewRepository = $om->getRepository(Review::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Review:list-byuser-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/feedbacks", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_feedbacks_old")
	 */
	public function oldShowFeedbacksAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_feedbacks', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/feedbacks", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_feedbacks")
	 * @Route("/@{username}/feedbacks/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_feedbacks_filter")
	 * @Route("/@{username}/feedbacks/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_feedbacks_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showFeedbacks.html.twig")
	 */
	public function showFeedbacksAction(Request $request, $username, $filter = "recent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_feedbacks', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$feedbackRepository = $om->getRepository(Feedback::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Feedback:list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/creations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_creations_old")
	 */
	public function oldShowCreationsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_creations', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/creations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_creations")
	 * @Route("/@{username}/creations/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_creations_filter")
	 * @Route("/@{username}/creations/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_creations_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showCreations.html.twig")
	 */
	public function showCreationsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Creation:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'creations',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/ateliers", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workshops_old")
	 */
	public function oldShowWorkshopsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_workshops', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/ateliers", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workshops")
	 * @Route("/@{username}/ateliers/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_workshops_filter")
	 * @Route("/@{username}/ateliers/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_workshops_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showWorkshops.html.twig")
	 */
	public function showWorkshopsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Workshop:list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'workshops',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/plans", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_plans_old")
	 */
	public function oldShowPlansAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_plans', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/plans", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_plans")
	 * @Route("/@{username}/plans/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_plans_filter")
	 * @Route("/@{username}/plans/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_plans_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showPlans.html.twig")
	 */
	public function showPlansAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Wonder/Plan:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'plans',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/pas-a-pas", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_howtos_old")
	 */
	public function showOldHowtosAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_howtos', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/pas-a-pas", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_howtos")
	 * @Route("/@{username}/pas-a-pas/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_howtos_filter")
	 * @Route("/@{username}/pas-a-pas/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_howtos_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showHowtos.html.twig")
	 */
	public function showHowtosAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Howto/Howto:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'howtos',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/trouvailles", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_finds_old")
	 */
	public function showOldFindsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_finds', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/trouvailles", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_finds")
	 * @Route("/@{username}/trouvailles/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_finds_filter")
	 * @Route("/@{username}/trouvailles/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_finds_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showFinds.html.twig")
	 */
	public function showFindsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Find/Find:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'finds',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/questions", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_questions_old")
	 */
	public function showOldQuestionsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_questions', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/questions", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_questions")
	 * @Route("/@{username}/questions/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_questions_filter")
	 * @Route("/@{username}/questions/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_questions_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showQuestions.html.twig")
	 */
	public function showQuestionsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Qa/Question:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'questions',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/reponses", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_answers_old")
	 */
	public function showOldAnswersAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_answers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/reponses", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_answers")
	 * @Route("/@{username}/reponses/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_answers_filter")
	 * @Route("/@{username}/reponses/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_answers_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showAnswers.html.twig")
	 */
	public function showAnswersAction(Request $request, $username, $filter = "recent", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_answers', array( 'username' => $user->getUsernameCanonical() )));
		}

		$om = $this->getDoctrine()->getManager();
		$answerRepository = $om->getRepository(Answer::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Qa/Answer:list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/{username}/graphismes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_graphics_old")
	 */
	public function showOldGraphicsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_graphics', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/graphismes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_graphics")
	 * @Route("/@{username}/graphismes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_graphics_filter")
	 * @Route("/@{username}/graphismes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_graphics_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showGraphics.html.twig")
	 */
	public function showGraphicsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Promotion/Graphic:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'graphics',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/processus", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workflows_old")
	 */
	public function showOldWorkflowsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_workflows', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/processus", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workflows")
	 * @Route("/@{username}/processus/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_workflows_filter")
	 * @Route("/@{username}/processus/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_workflows_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showWorkflows.html.twig")
	 */
	public function showWorkflowsAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Workflow:list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'workflows',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/annonces", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_offers_old")
	 */
	public function showOldOffersAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_offers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/annonces", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_offers")
	 * @Route("/@{username}/annonces/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_offers_filter")
	 * @Route("/@{username}/annonces/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_offers_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showOffers.html.twig")
	 */
	public function showOffersAction(Request $request, $username, $filter = null, $page = 0) {
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
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Offer/Offer:list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'offers',
		)), $isGrantedOwner);
	}

	/**
	 * @Route("/{username}/abonnements", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_following_old")
	 */
	public function showOldFollowingAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_following', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/abonnements", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_following")
	 * @Route("/@{username}/abonnements/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_following_filter")
	 * @Route("/@{username}/abonnements/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_following_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showFollowing.html.twig")
	 */
	public function showFollowingAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_following', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Following

		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Follower:following-list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'following',
		)));
	}

	/**
	 * @Route("/{username}/abonnes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_followers_old")
	 */
	public function showOldFollowersAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_followers', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/abonnes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_followers")
	 * @Route("/@{username}/abonnes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_followers_filter")
	 * @Route("/@{username}/abonnes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_followers_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showFollowers.html.twig")
	 */
	public function showFollowersAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_followers', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Followers

		$om = $this->getDoctrine()->getManager();
		$followerRepository = $om->getRepository(Follower::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Follower:followers-list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'followers'       => $paginator,
			'tab'             => 'followers',
		)));
	}

	/**
	 * @Route("/@{username}/membres", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_members")
	 * @Route("/@{username}/membres/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_members_filter")
	 * @Route("/@{username}/membres/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_members_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showMembers.html.twig")
	 */
	public function showMembersAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_following', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Member

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$paginator = $memberRepository->findPaginedByTeam($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_member_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'members'     => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Follower:member-list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => 'members',
		)));
	}

	/**
	 * @Route("/@{username}/collectifs", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_teams")
	 * @Route("/@{username}/collectifs/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_teams_filter")
	 * @Route("/@{username}/collectifs/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_teams_filter_page")
	 * @Template("LadbCoreBundle:Core/User:showTeams.html.twig")
	 */
	public function showTeamsAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show_teams', array( 'username' => $user->getUsernameCanonical() )));
		}

		// Teams

		$om = $this->getDoctrine()->getManager();
		$memberRepository = $om->getRepository(Member::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

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
			return $this->render('LadbCoreBundle:Core/Follower:teams-list-xhr.html.twig', $parameters);
		}

		return $this->_fillCommonShowParameters($user, array_merge($parameters, array(
			'tab' => '',
		)));
	}

	/**
	 * @Route("/@me", name="core_user_show_me")
	 */
	public function showMeAction() {
		$username = $this->getUser()->getUsernameCanonical();

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $username )));
	}

	/**
	 * @Route("/{username}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_old")
	 */
	public function oldShowAction($username) {
		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show")
	 */
	public function showAction($username) {
		$user = $this->retrieveUserByUsername($username);
		if ($user->getUsernameCanonical() != $username) {
			return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $user->getUsernameCanonical() )));
		}

		$CrawlerDetect = new CrawlerDetect();
		if ($CrawlerDetect->isCrawler()) {
			$forwardController = 'LadbCoreBundle:Core/User:showAbout'; // Return about page for Crawlers
		} else if ($user->getIsTeam()) {
			$forwardController = 'LadbCoreBundle:Core/User:showMembers';
		} else if ($user->getMeta()->getPublicCreationCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showCreations';
		} else if ($user->getMeta()->getPublicPlanCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showPlans';
		} else if ($user->getMeta()->getPublicHowtoCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showHowtos';
		} else if ($user->getMeta()->getPublicWorkshopCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showWorkshops';
		} else if ($user->getMeta()->getPublicFindCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showFinds';
		} else {
			$forwardController = 'LadbCoreBundle:Core/User:showAbout';
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
	 * @Route("/collectifs/new", name="core_user_team_new")
	 * @Template("LadbCoreBundle:Core/User/Team:new.html.twig")
	 */
	public function teamNewAction() {

		$userManager = $this->get('fos_user.user_manager');

		$team = $userManager->createUser();
		$form = $this->createForm(UserTeamType::class, $team);

		return array(
			'form' => $form->createView(),
		);
	}

	/**
	 * @Route("/collectifs/create", methods={"POST"}, name="core_user_team_create")
	 * @Template("LadbCoreBundle:Core/User/Team:new.html.twig")
	 */
	public function teamCreateAction(Request $request) {

		$this->createLock('core_user_team_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();
		$userManager = $this->get('fos_user.user_manager');

		$team = $userManager->createUser();
		$team->setEnabled(true);
		$team->setEmail(uniqid('', true).'-team@lairdubois.fr');		// Fake email - to bypass Registration validation on this field
		$team->setPlainPassword(bin2hex(random_bytes(20)));									// Put a random password - to bypass Registration validation on this field AND avoid logon on this account
		$form = $this->createForm(UserTeamType::class, $team);
		$form->handleRequest($request);

		if ($form->isValid()) {

			// Default avatar
			if (is_null($team->getAvatar())) {
				$userUtils = $this->get(UserUtils::NAME);
				$userUtils->createDefaultAvatar($team);
			}

			$team->setIsTeam(true);
			$team->setEmailCanonical($team->getEmail());
			$team->addRole('ROLE_TEAM');
			$userManager->updateUser($team);

			// Add team's creator as first member
			$memberUtils = $this->get(MemberUtils::NAME);
			$memberUtils->create($team, $this->getUser());

			$om->flush();

			// Search index update
			$searchUtils = $this->get(SearchUtils::NAME);
			$searchUtils->insertEntityToIndex($team);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.form.alert.team_success', array( '%displayname%' => $team->getDisplayname() )));

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
	 * @Template("LadbCoreBundle:Core/User:showAdmin.html.twig")
	 */
	public function showAdminAction($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
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
	 * @Route("/@{username}/admin/activate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_activate")
	 */
	public function adminActivateAction($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$userManager = $this->get('fos_user.user_manager');
		$manipulator = $this->get('fos_user.util.user_manipulator');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$manipulator->activate($username);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->insertEntityToIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.activate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/deactivate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_deactivate")
	 */
	public function adminDeactivateAction($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$userManager = $this->get('fos_user.user_manager');
		$manipulator = $this->get('fos_user.util.user_manipulator');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		$manipulator->deactivate($username);

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->deleteEntityFromIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.deactivate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/empty", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_empty")
	 */
	public function adminEmptyAction($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}

		// Remove likes
		$likableUtils = $this->get(LikableUtils::NAME);
		$likableUtils->deleteLikesByUser($user, false);

		// Remove followers / followings
		$followerUtils = $this->get(FollowerUtils::NAME);
		$followerUtils->deleteFollowersByUser($user, false);
		$followerUtils->deleteFollowingsByUser($user, false);

		// TODO

		$om->flush();

		// Search index update
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->replaceEntityInIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.empty_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/@{username}/admin/delete", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_delete")
	 */
	public function adminDeleteAction($username) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Access denied');
		}

		$om = $this->getDoctrine()->getManager();
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
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
		$searchUtils = $this->get(SearchUtils::NAME);
		$searchUtils->deleteEntityFromIndex($user);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.delete_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_welcome'));
	}

}