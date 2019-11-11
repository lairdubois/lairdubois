<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ladb\CoreBundle\Utils\VideoHostingUtils;

class VideoValueType extends AbstractValueType {

	private $videoHostingUtils;

	public function __construct(VideoHostingUtils $videoHostingUtils) {
		$this->videoHostingUtils = $videoHostingUtils;
	}

	/////

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('data', UrlType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				if (!is_null($value)) {
					$kindAndEmbedIdentifier = $this->videoHostingUtils->getKindAndEmbedIdentifier($value->getData());
					$value->setKind($kindAndEmbedIdentifier['kind']);
					$value->setEmbedIdentifier($kindAndEmbedIdentifier['embedIdentifier']);
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\Video',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_video';
	}

}
