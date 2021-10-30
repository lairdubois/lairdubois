<?php

namespace App\Controller\Blog;

use App\Controller\AbstractController;
use App\Controller\PublicationControllerTrait;
use App\Entity\Blog\Post;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Form\Type\Blog\PostType;
use App\Manager\Blog\PostManager;
use App\Manager\Core\WitnessManager;
use App\Model\HiddableInterface;
use App\Utils\BlockBodiedUtils;
use App\Utils\CollectionnableUtils;
use App\Utils\CommentableUtils;
use App\Utils\ExplorableUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\FollowerUtils;
use App\Utils\LikableUtils;
use App\Utils\SearchUtils;
use App\Utils\TagUtils;
use App\Utils\WatchableUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/blog")
 */
class PostController extends AbstractController {

	use PublicationControllerTrait;

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.PostManager::class,
            '?'.WitnessManager::class,
            '?'.BlockBodiedUtils::class,
            '?'.CollectionnableUtils::class,
            '?'.CommentableUtils::class,
            '?'.ExplorableUtils::class,
            '?'.FieldPreprocessorUtils::class,
            '?'.FollowerUtils::class,
            '?'.LikableUtils::class,
            '?'.SearchUtils::class,
            '?'.TagUtils::class,
            '?'.WatchableUtils::class,
        ));
    }

	/**
	 * @Route("/new", name="core_blog_post_new")
	 * @Template("Blog/Post/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_BLOG')", statusCode=404, message="Not allowed (core_blog_post_new)")
	 */
	public function new() {

		$post = new Post();
		$post->addBodyBlock(new \App\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(PostType::class, $post);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/create", methods={"POST"}, name="core_blog_post_create")
	 * @Template("Blog/Post/new.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_BLOG')", statusCode=404, message="Not allowed (core_blog_post_create)")
	 */
	public function create(Request $request) {

		$this->createLock('core_blog_post_create', false, self::LOCK_TTL_CREATE_ACTION, false);

		$om = $this->getDoctrine()->getManager();

		$post = new Post();
		$form = $this->createForm(PostType::class, $post);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($post);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($post);

			$post->setUser($this->getUser());

			$om->persist($post);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(new PublicationEvent($post), PublicationListener::PUBLICATION_CREATED);

			return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $post->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::class);



		return array(
			'post'         => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/lock", requirements={"id" = "\d+"}, defaults={"lock" = true}, name="core_blog_post_lock")
	 * @Route("/{id}/unlock", requirements={"id" = "\d+"}, defaults={"lock" = false}, name="core_blog_post_unlock")
	 */
	public function lockUnlock($id, $lock) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertLockUnlockable($post, $lock);

		// Lock or Unlock
		$postManager = $this->get(PostManager::class);
		if ($lock) {
			$postManager->lock($post);
		} else {
			$postManager->unlock($post);
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('wonder.creation.form.alert.'.($lock ? 'lock' : 'unlock').'_success', array( '%title%' => $post->getTitle() )));

		return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $post->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_blog_post_publish")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_BLOG')", statusCode=404, message="Not allowed (core_blog_post_publish)")
	 */
	public function publish($id) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertPublishable($post);

		// Publish
		$postManager = $this->get(PostManager::class);
		$postManager->publish($post);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.publish_success', array( '%title%' => $post->getTitle() )));

		return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $post->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_blog_post_unpublish")
	 */
	public function unpublish(Request $request, $id) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertUnpublishable($post);

		// Unpublish
		$postManager = $this->get(PostManager::class);
		$postManager->unpublish($post);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.unpublish_success', array( '%title%' => $post->getTitle() )));

		// Return to
		$returnToUrl = $request->get('rtu');
		if (is_null($returnToUrl)) {
			$returnToUrl = $request->headers->get('referer');
		}

		return $this->redirect($returnToUrl);
	}

	/**
	 * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="core_blog_post_edit")
	 * @Template("Blog/Post/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_BLOG')", statusCode=404, message="Not allowed (core_blog_post_edit)")
	 */
	public function edit($id) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertEditabable($post);

		$form = $this->createForm(PostType::class, $post);

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'post'         => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, methods={"POST"}, name="core_blog_post_update")
	 * @Template("Blog/Post/edit.html.twig")
	 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_BLOG')", statusCode=404, message="Not allowed (core_blog_post_update)")
	 */
	public function update(Request $request, $id) {

		$doUp = $request->get('ladb_do_up', false);

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertEditabable($post);

		$originalBodyBlocks = $post->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $post->getTags()->toArray();	// Need to be an array to copy values

		$post->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(PostType::class, $post);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::class);
			$blockUtils->preprocessBlocks($post, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::class);
			$fieldPreprocessorUtils->preprocessFields($post);

			if ($doUp) {
				$post->setChangedAt(new \DateTime());
			}
			$post->setUpdatedAt(new \DateTime());

			$om = $this->getDoctrine()->getManager();
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			if ($doUp) {
				$dispatcher->dispatch(new PublicationEvent($post, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_CHANGED);
			}
			$dispatcher->dispatch(new PublicationEvent($post, array( 'previouslyUsedTags' => $previouslyUsedTags )), PublicationListener::PUBLICATION_UPDATED);

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.update_success', array( '%title%' => $post->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(PostType::class, $post);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::class);

		return array(
			'post'     => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_blog_post_delete")
	 */
	public function delete($id) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertDeletable($post);

		// Delete
		$postManager = $this->get(PostManager::class);
		$postManager->delete($post);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.delete_success', array( '%title%' => $post->getTitle() )));

		return $this->redirect($this->generateUrl('core_blog_post_list'));
	}

	/**
	 * @Route("/{id}/widget", requirements={"id" = "\d+"}, name="core_blog_post_widget")
	 * @Template("Blog/Post/widget-xhr.html.twig")
	 */
	public function widget($id) {

		$post = $this->retrievePublication($id, Post::CLASS_NAME);
		$this->assertShowable($post, true);

		return array(
			'post' => $post,
		);
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_blog_post_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_blog_post_list_filter_page")
	 */
	public function goneList(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_blog_post_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_blog_post_list_page")
	 * @Template("Blog/Post/list.html.twig")
	 */
	public function list(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::class);

		// Elasticsearch paginiation limit
		if ($page > 624) {
			throw $this->createNotFoundException('Page limit reached (core_blog_post_list_page)');
		}

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort, &$noGlobalFilters, &$couldUseDefaultSort) use ($searchUtils) {
				switch ($facet->name) {

					// Filters /////

					case 'admin-all':
						if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {

							$filters[] = new \Elastica\Query\MatchAll();

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

							$noGlobalFilters = true;
						}
						break;

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addFilter(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addFilter(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

						}

						break;

					case 'period':

						if ($facet->value == 'last7days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-7d/d' ));

						} elseif ($facet->value == 'last30days') {

							$filters[] = new \Elastica\Query\Range('createdAt', array( 'gte' => 'now-30d/d' ));

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => $searchUtils->getSorterOrder($facet) ) );
						break;

					case 'sort-random':
						$sort = array( 'randomSeed' => isset($facet->value) ? $facet->value : '' );
						break;

					/////

					default:
						if (is_null($facet->name)) {

							$filter = new \Elastica\Query\QueryString($facet->value);
							$filter->setFields(array( 'title^100', 'body', 'tags.label' ));
							$filters[] = $filter;

							$couldUseDefaultSort = false;

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$this->pushGlobalVisibilityFilter($filters, true, false);

			},
			'blog_post',
			\App\Entity\Blog\Post::CLASS_NAME,
			'core_blog_post_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationsEvent($searchParameters['entities'], !$request->isXmlHttpRequest()), PublicationListener::PUBLICATIONS_LISTED);

		$parameters = array_merge($searchParameters, array(
			'posts' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('Blog/Post/list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_blog_post_show")
	 * @Template("Blog/Post/show.html.twig")
	 */
	public function show(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\App\Entity\Blog\Post::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::class);

		$id = intval($id);

		$post = $postRepository->findOneById($id);
		if (is_null($post)) {
			if ($response = $witnessManager->checkResponse(Post::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}
		$this->assertShowable($post);

		$explorableUtils = $this->get(ExplorableUtils::class);
		$similarPosts = $explorableUtils->getSimilarExplorables($post, 'blog_post', Post::CLASS_NAME);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(new PublicationEvent($post), PublicationListener::PUBLICATION_SHOWN);

		$likableUtils = $this->get(LikableUtils::class);
		$watchableUtils = $this->get(WatchableUtils::class);
		$commentableUtils = $this->get(CommentableUtils::class);
		$collectionnableUtils = $this->get(CollectionnableUtils::class);
		$followerUtils = $this->get(FollowerUtils::class);

		return array(
			'post'              => $post,
			'permissionContext' => $this->getPermissionContext($post),
			'similarPosts'      => $similarPosts,
			'likeContext'       => $likableUtils->getLikeContext($post, $this->getUser()),
			'watchContext'      => $watchableUtils->getWatchContext($post, $this->getUser()),
			'commentContext'    => $commentableUtils->getCommentContext($post),
			'collectionContext' => $collectionnableUtils->getCollectionContext($post),
			'followerContext'   => $followerUtils->getFollowerContext($post->getUser(), $this->getUser()),
		);
	}

}
