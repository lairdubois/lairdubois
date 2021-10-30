<?php

namespace App\Controller\Core;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\TypableUtils;
use App\Entity\Core\Spotlight;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/spotlights")
 */
class SpotlightController extends AbstractController {

    public static function getSubscribedServices() {
        return array_merge(parent::getSubscribedServices(), array(
            '?'.TypableUtils::class,
        ));
    }

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
