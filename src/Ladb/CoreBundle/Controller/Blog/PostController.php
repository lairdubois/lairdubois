<?php

namespace Ladb\CoreBundle\Controller\Blog;

use Ladb\CoreBundle\Model\HiddableInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Form\Type\Blog\PostType;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Manager\Blog\PostManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;

/**
 * @Route("/blog")
 */
class PostController extends Controller {

	/**
	 * @Route("/new", name="core_blog_post_new")
	 * @Template()
	 */
	public function newAction() {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_new)');
		}

		$post = new Post();
		$post->addBodyBlock(new \Ladb\CoreBundle\Entity\Core\Block\Text());	// Add a default Text body block
		$form = $this->createForm(PostType::class, $post);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/create", name="core_blog_post_create")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Blog/Post:new.html.twig")
	 */
	public function createAction(Request $request) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_create)');
		}

		$om = $this->getDoctrine()->getManager();

		$post = new Post();
		$form = $this->createForm(PostType::class, $post);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($post);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($post);

			$post->setUser($this->getUser());

			$om->persist($post);
			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_CREATED, new PublicationEvent($post));

			return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $post->getSluggedId() )));
		}

		// Flashbag
		$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'post'         => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/publish", requirements={"id" = "\d+"}, name="core_blog_post_publish")
	 */
	public function publishAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_publish)');
		}

		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);

		$post = $postRepository->findOneByIdJoinedOnUser($id);
		if (is_null($post)) {
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}
		if ($post->getIsDraft() === false) {
			throw $this->createNotFoundException('Already published (core_blog_post_publish)');
		}

		// Publish
		$postManager = $this->get(PostManager::NAME);
		$postManager->publish($post);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.publish_success', array( '%title%' => $post->getTitle() )));

		return $this->redirect($this->generateUrl('core_blog_post_show', array( 'id' => $post->getSluggedId() )));
	}

	/**
	 * @Route("/{id}/unpublish", requirements={"id" = "\d+"}, name="core_blog_post_unpublish")
	 */
	public function unpublishAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_unpublish)');
		}

		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);

		$post = $postRepository->findOneByIdJoinedOnUser($id);
		if (is_null($post)) {
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}
		if ($post->getIsDraft() === true) {
			throw $this->createNotFoundException('Already draft (core_blog_post_unpublish)');
		}

		// Unpublish
		$postManager = $this->get(PostManager::NAME);
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
	 * @Template()
	 */
	public function editAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_edit)');
		}

		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);

		$post = $postRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($post)) {
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}

		$form = $this->createForm(PostType::class, $post);

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'post'         => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/update", requirements={"id" = "\d+"}, name="core_blog_post_update")
	 * @Method("POST")
	 * @Template("LadbCoreBundle:Blog/Post:edit.html.twig")
	 */
	public function updateAction(Request $request, $id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_update)');
		}

		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);

		$post = $postRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($post)) {
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}

		$originalBodyBlocks = $post->getBodyBlocks()->toArray();	// Need to be an array to copy values
		$previouslyUsedTags = $post->getTags()->toArray();	// Need to be an array to copy values

		$post->resetBodyBlocks(); // Reset bodyBlocks array to consider form bodyBlocks order

		$form = $this->createForm(PostType::class, $post);
		$form->handleRequest($request);

		if ($form->isValid()) {

			$blockUtils = $this->get(BlockBodiedUtils::NAME);
			$blockUtils->preprocessBlocks($post, $originalBodyBlocks);

			$fieldPreprocessorUtils = $this->get(FieldPreprocessorUtils::NAME);
			$fieldPreprocessorUtils->preprocessFields($post);

			$post->setUpdatedAt(new \DateTime());

			$om->flush();

			// Dispatch publication event
			$dispatcher = $this->get('event_dispatcher');
			$dispatcher->dispatch(PublicationListener::PUBLICATION_UPDATED, new PublicationEvent($post, array( 'previouslyUsedTags' => $previouslyUsedTags )));

			// Flashbag
			$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.update_success', array( '%title%' => $post->getTitle() )));

			// Regenerate the form
			$form = $this->createForm(PostType::class, $post);

		} else {

			// Flashbag
			$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('default.form.alert.error'));

		}

		$tagUtils = $this->get(TagUtils::NAME);

		return array(
			'post'     => $post,
			'form'         => $form->createView(),
			'tagProposals' => $tagUtils->getProposals($post),
		);
	}

	/**
	 * @Route("/{id}/delete", requirements={"id" = "\d+"}, name="core_blog_post_delete")
	 */
	public function deleteAction($id) {
		if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
			throw $this->createNotFoundException('Not allowed (core_blog_post_delete)');
		}

		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);

		$post = $postRepository->findOneById($id);
		if (is_null($post)) {
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}

		// Delete
		$postManager = $this->get(PostManager::NAME);
		$postManager->delete($post);

		// Flashbag
		$this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('blog.post.form.alert.delete_success', array( '%title%' => $post->getTitle() )));

		return $this->redirect($this->generateUrl('core_blog_post_list'));
	}

	/**
	 * @Route("/{filter}", requirements={"filter" = "[a-z-]+"}, name="core_blog_post_list_filter")
	 * @Route("/{filter}/{page}", requirements={"filter" = "[a-z-]+", "page" = "\d+"}, name="core_blog_post_list_filter_page")
	 * @Template()
	 */
	public function goneListAction(Request $request, $filter, $page = 0) {
		throw new \Symfony\Component\HttpKernel\Exception\GoneHttpException();
	}

	/**
	 * @Route("/", name="core_blog_post_list")
	 * @Route("/{page}", requirements={"page" = "\d+"}, name="core_blog_post_list_page")
	 * @Template()
	 */
	public function listAction(Request $request, $page = 0) {
		$searchUtils = $this->get(SearchUtils::NAME);

		$searchParameters = $searchUtils->searchPaginedEntities(
			$request,
			$page,
			function($facet, &$filters, &$sort) {
				switch ($facet->name) {

					// Filters /////

					case 'mine':

						if ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {

							if ($facet->value == 'draft') {

								$filter = (new \Elastica\Query\BoolQuery())
									->addMust(new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsername()))
									->addMust(new \Elastica\Query\Range('visibility', array( 'lt' => HiddableInterface::VISIBILITY_PUBLIC )))
								;

							} else {

								$filter = new \Elastica\Query\MatchPhrase('user.username', $this->getUser()->getUsernameCanonical());
							}

							$filters[] = $filter;

							$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

						}

						break;

					case 'tag':

						$filter = new \Elastica\Query\QueryString($facet->value);
						$filter->setFields(array( 'tags.label' ));
						$filters[] = $filter;

						break;

					// Sorters /////

					case 'sort-recent':
						$sort = array( 'changedAt' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-views':
						$sort = array( 'viewCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-likes':
						$sort = array( 'likeCount' => array( 'order' => 'desc' ) );
						break;

					case 'sort-popular-comments':
						$sort = array( 'commentCount' => array( 'order' => 'desc' ) );
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

						}

				}
			},
			function(&$filters, &$sort) {

				$sort = array( 'changedAt' => array( 'order' => 'desc' ) );

			},
			function(&$filters) {

				$user = $this->getUser();
				$publicVisibilityFilter = new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PUBLIC ));
				if (!is_null($user)) {

					$filter = new \Elastica\Query\BoolQuery();
					$filter->addShould(
						$publicVisibilityFilter
					);
					$filter->addShould(
						(new \Elastica\Query\BoolQuery())
							->addMust(new \Elastica\Query\MatchPhrase('user.username', $user->getUsername()))
							->addMust(new \Elastica\Query\Range('visibility', array( 'gte' => HiddableInterface::VISIBILITY_PRIVATE )))
					);

				} else {
					$filter = $publicVisibilityFilter;
				}
				$filters[] = $filter;


			},
			'fos_elastica.index.ladb.blog_post',
			\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME,
			'core_blog_post_list_page'
		);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATIONS_LISTED, new PublicationsEvent($searchParameters['entities']));

		$parameters = array_merge($searchParameters, array(
			'posts' => $searchParameters['entities'],
		));

		if ($request->isXmlHttpRequest()) {
			return $this->render('LadbCoreBundle:Blog/Post:list-xhr.html.twig', $parameters);
		}

		return $parameters;
	}

	/**
	 * @Route("/{id}.html", name="core_blog_post_show")
	 * @Template()
	 */
	public function showAction(Request $request, $id) {
		$om = $this->getDoctrine()->getManager();
		$postRepository = $om->getRepository(\Ladb\CoreBundle\Entity\Blog\Post::CLASS_NAME);
		$witnessManager = $this->get(WitnessManager::NAME);

		$id = intval($id);

		$post = $postRepository->findOneByIdJoinedOnOptimized($id);
		if (is_null($post)) {
			if ($response = $witnessManager->checkResponse(Post::TYPE, $id)) {
				return $response;
			}
			throw $this->createNotFoundException('Unable to find Post entity (id='.$id.').');
		}
		if ($post->getIsDraft() === true) {
			if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
				if ($response = $witnessManager->checkResponse(Post::TYPE, $id)) {
					return $response;
				}
				throw $this->createNotFoundException('Not allowed (core_blog_post_show)');
			}
		}

		$explorableUtils = $this->get(ExplorableUtils::NAME);
		$similarPosts = $explorableUtils->getSimilarExplorables($post, 'fos_elastica.index.ladb.blog_post', Post::CLASS_NAME);

		// Dispatch publication event
		$dispatcher = $this->get('event_dispatcher');
		$dispatcher->dispatch(PublicationListener::PUBLICATION_SHOWN, new PublicationEvent($post));

		$likableUtils = $this->get(LikableUtils::NAME);
		$watchableUtils = $this->get(WatchableUtils::NAME);
		$commentableUtils = $this->get(CommentableUtils::NAME);
		$followerUtils = $this->get(FollowerUtils::NAME);

		return array(
			'post'            => $post,
			'similarPosts'    => $similarPosts,
			'likeContext'     => $likableUtils->getLikeContext($post, $this->getUser()),
			'watchContext'    => $watchableUtils->getWatchContext($post, $this->getUser()),
			'commentContext'  => $commentableUtils->getCommentContext($post),
			'followerContext' => $followerUtils->getFollowerContext($post->getUser(), $this->getUser()),
		);
	}

}
