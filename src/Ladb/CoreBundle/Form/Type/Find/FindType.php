<?php

namespace Ladb\CoreBundle\Form\Type\Find;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\TagsToNamesTransformer;
use Ladb\CoreBundle\Entity\Find\Find;
use Ladb\CoreBundle\Entity\Find\Content\Website;
use Ladb\CoreBundle\Entity\Find\Content\Video;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Ladb\CoreBundle\Utils\FindUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;

class FindType extends AbstractType {

	private $om;
	private $findUtils;
	private $videoHostingUtils;
	private $localisableUtils;

	public function __construct(ObjectManager $om, FindUtils $findUtils, VideoHostingUtils $videoHostingUtils, LocalisableUtils $localisableUtils) {
		$this->om = $om;
		$this->findUtils = $findUtils;
		$this->videoHostingUtils = $videoHostingUtils;
		$this->localisableUtils = $localisableUtils;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add('body', TextareaType::class)
			->add('contentType', ChoiceType::class, array(
				'choices'     => array_flip(array(Find::CONTENT_TYPE_LINK => 'find.content.link.name', Find::CONTENT_TYPE_GALLERY => 'find.content.gallery.name', Find::CONTENT_TYPE_EVENT => 'find.content.event.name')),
			))
			->add('link', Content\LinkType::class, array(
				'mapped'      => false,
				'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('gallery', Content\GalleryType::class, array(
				'mapped'      => false,
				'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('event', Content\EventType::class, array(
				'mapped'      => false,
				'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToNamesTransformer($this->om))
			)
		;

		$builder->addEventListener(
			FormEvents::POST_SET_DATA,
			function(FormEvent $event) {
				$find = $event->getData();

				switch ($find->getKind()) {

					case Find::KIND_WEBSITE:
						$event->getForm()->get('link')->setData($find->getContent());
						$event->getForm()->get('contentType')->setData(Find::CONTENT_TYPE_LINK);
						break;

					case Find::KIND_VIDEO:
						$event->getForm()->get('link')->setData($find->getContent());
						$event->getForm()->get('contentType')->setData(Find::CONTENT_TYPE_LINK);
						break;

					case Find::KIND_GALLERY:
						$event->getForm()->get('gallery')->setData($find->getContent());
						$event->getForm()->get('contentType')->setData(Find::CONTENT_TYPE_GALLERY);
						break;

					case Find::KIND_EVENT:
						$event->getForm()->get('event')->setData($find->getContent());
						$event->getForm()->get('contentType')->setData(Find::CONTENT_TYPE_EVENT);
						break;

				}

			}
		);

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$find = $event->getForm()->getData();

				switch ($event->getForm()->get('contentType')->getData()) {

					case Find::CONTENT_TYPE_LINK:
						$link = $event->getForm()->get('link')->getData();
						$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($link->getUrl());
						if ($kindAndEmbedIdentifier['kind'] == VideoHostingUtils::KIND_UNKNOW) {
							$website = new Website();
							$website->setId($link->getId());
							$website->setUrl($link->getUrl());
							$website->setThumbnail($link->getThumbnail());
							$find->setContent($website);
							$find->setKind(Find::KIND_WEBSITE);
						} else {
							$video = new Video();
							$video->setId($link->getId());
							$video->setUrl($link->getUrl());
							$video->setThumbnail($link->getThumbnail());
							$video->setKind($kindAndEmbedIdentifier['kind']);
							$video->setEmbedIdentifier($kindAndEmbedIdentifier['embedIdentifier']);
							$find->setContent($video);
							$find->setKind(Find::KIND_VIDEO);
						}
						break;

					case Find::CONTENT_TYPE_GALLERY:
						$gallery = $event->getForm()->get('gallery')->getData();
						$this->localisableUtils->geocodeLocation($gallery);
						$find->setContent($gallery);
						$find->setKind(Find::KIND_GALLERY);
						break;

					case Find::CONTENT_TYPE_EVENT:
						$event = $event->getForm()->get('event')->getData();
						$this->localisableUtils->geocodeLocation($event);
						$find->setContent($event);
						$find->setKind(Find::KIND_EVENT);

						$startAt = null;
						$endAt = null;
						if (!is_null($event->getStartDate())) {
							$startAt = clone $event->getStartDate();
							if (!is_null($event->getStartTime())) {
								$startAt->add(new \DateInterval('PT'.$event->getStartTime()->format('G').'H'.$event->getStartTime()->format('i').'M'));
							}

							if (!is_null($event->getEndDate())) {
								$endAt = clone $event->getEndDate();
								if (!is_null($event->getEndTime())) {
									$endAt->add(new \DateInterval('PT'.$event->getEndTime()->format('G').'H'.$event->getEndTime()->format('i').'M'));
								} else {
									$endAt->add(new \DateInterval('P1D'));
								}
							} else {
								$endAt = clone $event->getStartDate();
								$endAt->add(new \DateInterval('P1D'));
							}
						}

						$event->setStartAt($startAt);
						$event->setEndAt($endAt);

						break;

				}

			}
		);

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$find = $event->getForm()->getData();
				$this->findUtils->generateMainPicture($find);
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class'        => 'Ladb\CoreBundle\Entity\Find\Find',
			'validation_groups' => function (FormInterface $form) {
				$find = $form->getData();
				switch ($find->getContentType()) {

					case Find::CONTENT_TYPE_LINK:
						return array('Default', 'link');

					case Find::CONTENT_TYPE_GALLERY:
						return array('Default', 'gallery');

					case Find::CONTENT_TYPE_EVENT:
						return array('Default', 'event');

				}
			},
		));
	}

	public function getBlockPrefix() {
		return 'ladb_find_find';
	}

}
