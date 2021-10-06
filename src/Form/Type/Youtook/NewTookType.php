<?php

namespace App\Form\Type\Youtook;

use App\Manager\Core\PictureManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Core\Picture;
use App\Utils\VideoHostingUtils;

class NewTookType extends AbstractType {

	private $om;
	private $videoHostingUtils;
	private $pictureManager;

	public function __construct(ObjectManager $om, VideoHostingUtils $videoHostingUtils, PictureManager $pictureManager) {
		$this->om = $om;
		$this->videoHostingUtils = $videoHostingUtils;
		$this->pictureManager = $pictureManager;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('url')
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$took = $event->getForm()->getData();

				$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($took->getUrl());
				if ($kindAndEmbedIdentifier['kind'] == VideoHostingUtils::KIND_YOUTUBE) {

					$took->setKind($kindAndEmbedIdentifier['kind']);
					$took->setEmbedIdentifier($kindAndEmbedIdentifier['embedIdentifier']);

					// Fetch video data from YouTube
					$data = $this->videoHostingUtils->getVideoGwData($took->getKind(), $took->getEmbedIdentifier());
					if (!is_null($data)) {

						if (is_null($took->getMainPicture())) {
							$thumbnailUrl = $data['videoData']['thumbnail_loc'];
							if (!is_null($thumbnailUrl)) {

								// Grab picture
								$mainPicture = $this->pictureManager->createFromUrl($thumbnailUrl);

								$took->setThumbnailUrl($thumbnailUrl);
								$took->setMainPicture($mainPicture);
							}
						}

						// Fill took's properties
						$took->setTitle($data['videoData']['title']);
						$took->setBody($data['videoData']['description']);
						$took->setChannelId($data['channelData']['id']);
						$took->setChannelThumbnailUrl($data['channelData']['thumbnail_loc']);
						$took->setChannelTitle($data['channelData']['title']);

					}

				}

			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Youtook\Took',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_youtook_new_took';
	}

}
