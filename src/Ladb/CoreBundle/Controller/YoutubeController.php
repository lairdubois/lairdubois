<?php

namespace Ladb\CoreBundle\Controller;

use Ladb\CoreBundle\Entity\Youtube\Video;
use Ladb\CoreBundle\Utils\OpenGraphUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Ladb\CoreBundle\Utils\VideoHostingUtils;

/**
 * @Route("/yt")
 */
class YoutubeController extends Controller {

	/**
	 * @Route("/{embedIdentifier}/new", name="core_youtube_new")
	 * @Template()
	 */
	public function newAction($embedIdentifier) {
		$om = $this->getDoctrine()->getManager();

		$videoRepository = $this->getDoctrine()->getRepository(Video::CLASS_NAME);
		if (!$videoRepository->existsByEmbedIdentifier($embedIdentifier)) {

			// Try to fetch data from YouTube
			$videoHostingUtils = $this->get(VideoHostingUtils::NAME);
			$data = $videoHostingUtils->getVideoGwData(VideoHostingUtils::KIND_YOUTUBE, $embedIdentifier);

			if (is_null($data)) {
				throw $this->createNotFoundException('Video not found (core_youtube_show)');
			}

			// Create a new video entity and cache it in DB
			$video = new Video();
			$video->setUser($this->getUser());
			$video->setEmbedIdentifier($embedIdentifier);
			$video->setThumbnailLoc($data['videoData']['thumbnail_loc']);
			$video->setTitle($data['videoData']['title']);
			$video->setDescription($data['videoData']['description']);
			$video->setChannelId($data['channelData']['id']);
			$video->setChannelThumbnailLoc($data['channelData']['thumbnail_loc']);
			$video->setChannelTitle($data['channelData']['title']);
			$video->setChannelDescription($data['channelData']['description']);

			$om->persist($video);
			$om->flush();

			// Scrape Open Graph URL
			$openGraphUtils = $this->get(OpenGraphUtils::NAME);
			$openGraphUtils->scrape($this->generateUrl('core_youtube_show', array( 'embedIdentifier' => $embedIdentifier )));

		}

		return $this->redirect($this->generateUrl('core_youtube_show', array( 'embedIdentifier' => $embedIdentifier )));
	}

	/**
	 * @Route("/{embedIdentifier}", name="core_youtube_show")
	 * @Template()
	 */
	public function showAction($embedIdentifier) {

		$videoRepository = $this->getDoctrine()->getRepository(Video::CLASS_NAME);
		$video = $videoRepository->findOneByEmbedIdentifier($embedIdentifier);

		if (is_null($video)) {
			return $this->redirect($this->generateUrl('core_youtube_new', array( 'embedIdentifier' => $embedIdentifier )));
		}

		return array(
			'video' => $video,
		);
	}

}
