<?php

namespace App\Form\Type\Find;

use App\Entity\Find\Content\Video;
use App\Entity\Find\Content\Website;
use App\Entity\Find\Find;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\Type\PolyCollectionType;
use App\Utils\LinkUtils;
use App\Utils\LocalisableUtils;
use App\Utils\VideoHostingUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FindType extends AbstractType {

	private $om;
	private $videoHostingUtils;
	private $localisableUtils;

	public function __construct(EntityManagerInterface $om, VideoHostingUtils $videoHostingUtils, LocalisableUtils $localisableUtils) {
		$this->om = $om;
		$this->videoHostingUtils = $videoHostingUtils;
		$this->localisableUtils = $localisableUtils;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'        => array(
					\App\Form\Type\Block\TextBlockType::class,
					\App\Form\Type\Block\GalleryBlockType::class,
					\App\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'    => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options'      => array(
					'em' => $this->om,
				),
				'constraints'  => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('contentType', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
			->add('link', Content\LinkType::class, array(
				'mapped'      => false,
				'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('gallery', Content\GalleryType::class, array(
				'mapped'      => false,
				'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
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
						$canonicalUrl = $link->getUrl(); // TODO Google don't like this method : $this->linkUtils->getCanonicalUrl($link->getUrl());
						$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($link->getUrl());
						if ($kindAndEmbedIdentifier['kind'] == VideoHostingUtils::KIND_UNKNOW) {
							$website = new Website();
							$website->setId($link->getId());
							$website->setUrl($canonicalUrl);
							$website->setThumbnail($link->getThumbnail());
							$find->setContent($website);
							$find->setKind(Find::KIND_WEBSITE);
						} else {
							$video = new Video();
							$video->setId($link->getId());
							$video->setUrl($canonicalUrl);
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

				}

			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class'        => 'App\Entity\Find\Find',
			'validation_groups' => function (FormInterface $form) {
				$find = $form->getData();
				switch ($find->getContentType()) {

					case Find::CONTENT_TYPE_LINK:
						return array('Default', 'link');

					case Find::CONTENT_TYPE_GALLERY:
						return array('Default', 'gallery');

				}
				return array('Default');
			},
		));
	}

	public function getBlockPrefix() {
		return 'ladb_find_find';
	}

}
