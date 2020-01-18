<?php

namespace Ladb\CoreBundle\Controller\Core;

use Ladb\CoreBundle\Controller\AbstractController;
use Ladb\CoreBundle\Entity\Core\Tip;
use Ladb\CoreBundle\Utils\MaybeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ladb\CoreBundle\Entity\Knowledge\School;
use Ladb\CoreBundle\Entity\Qa\Question;
use Ladb\CoreBundle\Utils\CollectionnableUtils;
use Ladb\CoreBundle\Manager\Wonder\CreationManager;
use Ladb\CoreBundle\Manager\Core\WitnessManager;
use Ladb\CoreBundle\Entity\Workflow\Workflow;
use Ladb\CoreBundle\Model\HiddableInterface;
use Ladb\CoreBundle\Utils\StripableUtils;
use Ladb\CoreBundle\Form\Type\Wonder\CreationType;
use Ladb\CoreBundle\Utils\PaginatorUtils;
use Ladb\CoreBundle\Utils\LikableUtils;
use Ladb\CoreBundle\Utils\WatchableUtils;
use Ladb\CoreBundle\Utils\CommentableUtils;
use Ladb\CoreBundle\Utils\FollowerUtils;
use Ladb\CoreBundle\Utils\ExplorableUtils;
use Ladb\CoreBundle\Utils\TagUtils;
use Ladb\CoreBundle\Utils\FieldPreprocessorUtils;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\BlockBodiedUtils;
use Ladb\CoreBundle\Utils\PicturedUtils;
use Ladb\CoreBundle\Utils\EmbeddableUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use Ladb\CoreBundle\Event\PublicationEvent;
use Ladb\CoreBundle\Event\PublicationListener;
use Ladb\CoreBundle\Event\PublicationsEvent;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Entity\Howto\Howto;
use Ladb\CoreBundle\Entity\Knowledge\Provider;
use Ladb\CoreBundle\Entity\Core\Spotlight;
use Ladb\CoreBundle\Entity\Wonder\Plan;
use Ladb\CoreBundle\Entity\Wonder\Creation;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Entity\Find\Find;

/**
 * @Route("/spotlights")
 */
class SpotlightController extends AbstractController {

	private function _retrieveRelatedEntity($entityType, $entityId) {
		$typableUtils = $this->get(TypableUtils::NAME);
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
	public function feedAction() {
		$om = $this->getDoctrine()->getManager();
		$spotlightRepository = $om->getRepository(Spotlight::CLASS_NAME);
		$translator = $this->get('translator');
		$typableUtils = $this->get(TypableUtils::NAME);

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
				->title($entity->getTitle())
				->description($entity->getBodyExtract().'<br>#lairdubois '.implode(' ', array_map(function($tag) { return '#'.$tag->getLabel(); }, $entity->getTags()->toArray())).'<br><a href="'.$entityShowPath.'">Lire la suite...</a>')
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
