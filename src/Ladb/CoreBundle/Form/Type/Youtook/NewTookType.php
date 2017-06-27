<?php

namespace Ladb\CoreBundle\Form\Type\Youtook;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Entity\Core\Picture;
use Ladb\CoreBundle\Utils\VideoHostingUtils;

class NewTookType extends AbstractType {

	private $om;
	private $videoHostingUtils;

	public function __construct(ObjectManager $om, VideoHostingUtils $videoHostingUtils) {
		$this->om = $om;
		$this->videoHostingUtils = $videoHostingUtils;
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
								$mainPicture = new Picture();
								$mainPicture->setMasterPath(sha1(uniqid(mt_rand(), true)).'.jpg');

								if (copy($thumbnailUrl, $mainPicture->getAbsolutePath())) {

									list($width, $height) = getimagesize($mainPicture->getAbsolutePath());
									$mainPicture->setWidth($width);
									$mainPicture->setHeight($height);
									$mainPicture->setHeightRatio100($width > 0 ? $height / $width * 100 : 100);

									$this->om->persist($mainPicture);
								}

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
			'data_class' => 'Ladb\CoreBundle\Entity\Youtook\Took',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_youtook_new_took';
	}

}
