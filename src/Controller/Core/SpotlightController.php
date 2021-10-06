<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use App\Entity\Core\Tip;
use App\Utils\MaybeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Knowledge\School;
use App\Entity\Qa\Question;
use App\Utils\CollectionnableUtils;
use App\Manager\Wonder\CreationManager;
use App\Manager\Core\WitnessManager;
use App\Entity\Workflow\Workflow;
use App\Model\HiddableInterface;
use App\Utils\StripableUtils;
use App\Form\Type\Wonder\CreationType;
use App\Utils\PaginatorUtils;
use App\Utils\LikableUtils;
use App\Utils\WatchableUtils;
use App\Utils\CommentableUtils;
use App\Utils\FollowerUtils;
use App\Utils\ExplorableUtils;
use App\Utils\TagUtils;
use App\Utils\FieldPreprocessorUtils;
use App\Utils\SearchUtils;
use App\Utils\BlockBodiedUtils;
use App\Utils\PicturedUtils;
use App\Utils\EmbeddableUtils;
use App\Utils\TypableUtils;
use App\Event\PublicationEvent;
use App\Event\PublicationListener;
use App\Event\PublicationsEvent;
use App\Entity\Blog\Post;
use App\Entity\Howto\Howto;
use App\Entity\Knowledge\Provider;
use App\Entity\Core\Spotlight;
use App\Entity\Wonder\Plan;
use App\Entity\Wonder\Creation;
use App\Entity\Core\View;
use App\Entity\Find\Find;

/**
 * @Route("/spotlights")
 */
class SpotlightController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::class);
		try {
			$entity = $typableUtils->findTypable($entityType, $entityId);
		} catch (\Exception $e) {
			throw $this->createNotFoundException($e->getMessage());
		}
		return $entity;
	}

	/////

	/**
	 * @Route("/feed.xml", name="core_spotlight_feed")
	 */
	public function feed() {
		$om = $this->getDoctrine()->getManager();
		$spotlightRepository = $om->getRepository(Spotlight::CLASS_NAME);
		$translator = $this->get('translator');
		$typableUtils = $this->get(TypableUtils::class);

		$feed = new \Suin\RSSWriter\Feed();

		$channel = new \Suin\RSSWriter\Channel();
		$channel
			->title('L\'Air du Bois : '.$translator->trans('default.spotlights'))
			->description($translator->trans('default.spotlights_description'))
			->feedUrl($this->generateUrl('core_spotlight_feed', array(), \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))
			->language('fr-FR')
			->pubDate((new \DateTime())->getTimestamp())
			->lastBuildDate((new \DateTime())->getTimestamp())
			->ttl(60)
			->appendTo($feed);

		$spotlights = $spotlightRepository->findPagined(0, 15);
		foreach ($spotlights as $spotlight) {

			$entity = $this->_retrieveRelatedEntity($spotlight->getEntityType(), $spotlight->getEntityId());

			$entityShowPath = $typableUtils->getUrlAction($entity);
			$entityStripPath = $typableUtils->getUrlAction($entity, 'strip', true, false);

			$item = new \Suin\RSSWriter\Item();
			$item
				->title($entity->getTitle().' '.$translator->trans('default.by').' '.$entity->getUser()->getDisplayname())
				->description($entity->getBodyExtract().' #lairdubois '.implode(' ', array_map(function($tag) { return '#'.$tag->getLabel(); }, $entity->getTags()->toArray())))
				->url($entityShowPath)
				->author($entity->getUser()->getDisplayName())
				->pubDate($entity->getChangedAt()->getTimestamp())
				->guid($entityShowPath, true)
				->enclosure($entityStripPath, 0, 'image/png')
			;

			foreach ($entity->getTags() as $tag) {
				$item->category($tag->getLabel());
			}

			$item->appendTo($channel);

		}

		return new Response(
			$feed->render(),
			Response::HTTP_OK,
			array( 'content-type' => 'application/rss+xml' )
		);
	}

}
