<?php

namespace Ladb\CoreBundle\Controller\Core;

use Elastica\Query\Exists;
use Ladb\CoreBundle\Entity\Knowledge\School\Testimonial;
use Ladb\CoreBundle\Entity\Promotion\Graphic;
use Ladb\CoreBundle\Entity\Qa\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Core\Comment;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Core\Follower;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Core\Like;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Workshop;
use Ladb\CoreBundle\Entity\Core\Registration;
use Ladb\CoreBundle\Form\Type\UserSettingsType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Ladb\CoreBundle\Utils\MailerUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\UserUtils;

/**
 * Creation controller.
 *
 * @Route("/")
 */
class UserController extends Controller {

	/**
	 * @Route("/email/check", name="core_user_email_check")
	 * @Template()
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
	 * @Template()
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
	 * @Route("/boiseux/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_user_list_filter")
	 * @Route("/boiseux/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_list_filter_page")
	 * @Template()
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/boiseux", name="core_user_list")
	 * @Route("/boiseux/{page}", requirements={"page" = "\d+"}, name="core_user_list_page")
	 * @Route("/boiseux.geojson", defaults={"_format" = "json", "page"=-1, "layout"="geojson"}, name="core_user_list_geojson")
	 * @Template()
	 */
	public function listAction(Request $request, $page = 0, $layout = 'view') {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					// Filters /////

					case 'skill':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'skills.label' ));
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
						$bounds = $localisableUtils->getTopLeftBottomRightBounds($facet->value);

						if (!is_null($bounds)) {
							$filter = new \Elastica\Query\GeoBoundingBox('geoPoint', $bounds);
							$filters[] = $filter;
						}

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'createdAt' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-followers':
						$sort = array( 'followerCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'recievedLikeCount' => array( 'order' => 'desc' ) );
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

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'followerCount' => array( 'order' => 'desc' ) );

			},
			null,
			'fos_elastica.index.ladb.core_user',
			\Ladb\CoreBundle\Entity\Core\User::CLASS_NAME,
			'core_user_list_page'
		);

		$parameters = array_merge($searchParameters, array(
			'users' => $searchParameters['entities'],
		));

		if ($layout == 'geojson') {

			$features = array();
			foreach ($searchParameters['entities'] as $user) {
				$properties = array(
					'type' => $user->getAccountType(),
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
	 * @Route("/parametres", name="core_user_settings")
	 * @Template()
	 */
	public function settingsAction(Request $request) {
		$om = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		$form = $this->createForm(UserSettingsType::class, $user);

		if ($request->isMethod('post')) {
			$form->handleRequest($request);

			if ($form->isValid()) {

				$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
				$fieldPreprocessorUtils->preprocessFields($user->getBiography());

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
	 * @Route("/{username}/location.geojson", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_location", defaults={"_format" = "json"})
	 * @Template("LadbCoreBundle:Core/User:location.geojson.twig")
	 */
	public function locationAction(Request $request, $username) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		$features = array();
		if (!is_null($user->getLongitude()) && !is_null($user->getLatitude())) {
			$properties = array(
				'type' => $user->getAccountType(),
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
	 * @Route("/{username}/card.xhr", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_card")
	 * @Template("LadbCoreBundle:Core/User:card-xhr.html.twig")
	 */
	public function cardAction(Request $request, $username) {
		if (!$request->isXmlHttpRequest()) {
			throw $this->createNotFoundException('Only XML request allowed.');
		}

		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		return array(
			'user' => $user,
		);
	}

	/**
	 * @Route("/{username}/a-propos", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_about")
	 * @Template()
	 */
	public function showAboutAction($username) {
		$om = $this->getDoctrine()->getManager();
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		$testimonialRepository = $om->getRepository(Testimonial::CLASS_NAME);
		$testimonials = $testimonialRepository->findByUser($user);

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'user'            => $user,
			'biography'       => $user->getBiography(),
			'tab'             => 'about',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
			'hasMap'          => !is_null($user->getLatitude()) && !is_null($user->getLongitude()),
			'testimonials'    => $testimonials,
		);
	}

	/**
	 * @Route("/{username}/coups-de-coeur", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_likes")
	 * @Route("/{username}/coups-de-coeur/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "sent|recieved"}, name="core_user_show_likes_filter")
	 * @Route("/{username}/coups-de-coeur/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_likes_filter_page")
	 * @Template()
	 */
	public function showLikesAction(Request $request, $username, $filter = "sent", $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		$om = $this->getDoctrine()->getManager();
		$likeRepository = $om->getRepository(Like::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $likeRepository->findPaginedByUser($user, $offset, $limit, $filter);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_likes_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $filter == 'recieved' ? $user->getRecievedLikeCount() : $user->getSentLikeCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Like:list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => '',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/commentaires", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_comments")
	 * @Route("/{username}/commentaires/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_comments_filter")
	 * @Route("/{username}/commentaires/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_comments_filter_page")
	 * @Template()
	 */
	public function showCommentsAction(Request $request, $username, $filter = "recent", $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		$om = $this->getDoctrine()->getManager();
		$commentRepository = $om->getRepository(Comment::CLASS_NAME);
		$paginatorUtils = $this->get(PaginatorUtils::NAME);

		$offset = $paginatorUtils->computePaginatorOffset($page);
		$limit = $paginatorUtils->computePaginatorLimit($page);
		$items = $commentRepository->findPaginedByUserGroupByEntityType($user, $offset, $limit);
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_comments_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $user->getCommentCount());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'items'       => $items,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Core/Comment:list-byuser-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => '',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/creations", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_creations")
	 * @Route("/{username}/creations/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_creations_filter")
	 * @Route("/{username}/creations/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_creations_filter_page")
	 * @Template()
	 */
	public function showCreationsAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $creationRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'creations',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/ateliers", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_workshops")
	 * @Route("/{username}/ateliers/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_workshops_filter")
	 * @Route("/{username}/ateliers/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_workshops_filter_page")
	 * @Template()
	 */
	public function showWorkshopsAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $workshopRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'workshops',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/plans", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_plans")
	 * @Route("/{username}/plans/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_plans_filter")
	 * @Route("/{username}/plans/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_plans_filter_page")
	 * @Template()
	 */
	public function showPlansAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $planRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'plans',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/projets", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_projects")
	 */
	public function showProjectsAction($username) {
		return $this->redirect($this->generateUrl('core_user_show_howtos', array( 'username' => $username )));
	}

	/**
	 * @Route("/{username}/pas-a-pas", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_howtos")
	 * @Route("/{username}/pas-a-pas/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_howtos_filter")
	 * @Route("/{username}/pas-a-pas/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_howtos_filter_page")
	 * @Template()
	 */
	public function showHowtosAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $howtoRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'howtos',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/trouvailles", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_finds")
	 * @Route("/{username}/trouvailles/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_finds_filter")
	 * @Route("/{username}/trouvailles/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_finds_filter_page")
	 * @Template()
	 */
	public function showFindsAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $findRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
		$pageUrls = $paginatorUtils->generatePrevAndNextPageUrl('core_user_show_finds_filter_page', array( 'username' => $user->getUsernameCanonical(), 'filter' => $filter ), $page, $paginator->count());

		$parameters = array(
			'filter'      => $filter,
			'prevPageUrl' => $pageUrls->prev,
			'nextPageUrl' => $pageUrls->next,
			'finds'       => $paginator,
		);

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Find:list-xhr.html.twig', $parameters);
		}

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'finds',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/questions", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_questions")
	 * @Route("/{username}/questions/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_questions_filter")
	 * @Route("/{username}/questions/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_questions_filter_page")
	 * @Template()
	 */
	public function showQuestionsAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $questionRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'questions',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/graphismes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_graphics")
	 * @Route("/{username}/graphismes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_graphics_filter")
	 * @Route("/{username}/graphismes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_graphics_filter_page")
	 * @Template()
	 */
	public function showGraphicsAction(Request $request, $username, $filter = null, $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		// Default filter

		if (is_null($filter)) {
			if ($this->get('security.authorization_checker')->isGranted('ROLE_USER') && $user->getId() == $this->getUser()->getId()) {
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
		$paginator = $graphicRepository->findPaginedByUser($user, $offset, $limit, $filter, $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || !is_null($this->getUser()) && $user->getId() == $this->getUser()->getId());
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'graphics',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/abonnements", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_following")
	 * @Route("/{username}/abonnements/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_following_filter")
	 * @Route("/{username}/abonnements/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_following_filter_page")
	 * @Template()
	 */
	public function showFollowingAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
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

		return array_merge($parameters, array(
			'user'            => $user,
			'tab'             => 'following',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/{username}/abonnes", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_followers")
	 * @Route("/{username}/abonnes/{filter}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+"}, name="core_user_show_followers_filter")
	 * @Route("/{username}/abonnes/{filter}/{page}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$", "filter" = "[a-z-]+", "page" = "\d+"}, name="core_user_show_followers_filter_page")
	 * @Template()
	 */
	public function showFollowersAction(Request $request, $username, $filter = "popular-followers", $page = 0) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
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

		return array_merge($parameters, array(
			'user'            => $user,
			'followers'       => $paginator,
			'tab'             => 'followers',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		));
	}

	/**
	 * @Route("/me", name="core_user_show_me")
	 * @Template()
	 */
	public function showMeAction() {
		$username = $this->getUser()->getUsernameCanonical();

		return $this->redirect($this->generateUrl('core_user_show', array( 'username' => $username )));
	}

	/**
	 * @Route("/{username}", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show")
	 * @Template()
	 */
	public function showAction($username) {
		$userManager = $this->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($username);
		if (is_null($user)) {
			throw $this->createNotFoundException('User not found');
		}
		if (!$user->isEnabled() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('User not enabled');
		}

		if ($user->getPublishedCreationCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showCreations';
		} else if ($user->getPublishedPlanCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showPlans';
		} else if ($user->getPublishedHowtoCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showHowtos';
		} else if ($user->getPublishedWorkshopCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showWorkshops';
		} else if ($user->getPublishedFindCount() > 0) {
			$forwardController = 'LadbCoreBundle:Core/User:showFinds';
		} else {
			$forwardController = 'LadbCoreBundle:Core/User:showAbout';
		}

		$response = $this->forward($forwardController, array(
			'username'  => $username,
		));
		return $response;
	}

	// Admin /////

	/**
	 * @Route("/{username}/admin", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_show_admin")
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

		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'user'            => $user,
			'registration'    => $registration,
			'tab'             => 'admin',
			'followerContext' => $followerUtils->getFollowerContext($user, $this->getUser()),
		);
	}

	/**
	 * @Route("/{username}/admin/activate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_activate")
	 * @Template()
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

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.activate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/{username}/admin/deactivate", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_deactivate")
	 * @Template()
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

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.deactivate_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

	/**
	 * @Route("/{username}/admin/empty", requirements={"username" = "^[a-zA-Z0-9]{3,25}$"}, name="core_user_admin_empty")
	 * @Template()
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

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.admin.alert.empty_success', array( '%displayname%' => $user->getDisplayname() )));

		return $this->redirect($this->generateUrl('core_user_show_admin', array( 'username' => $username )));
	}

}