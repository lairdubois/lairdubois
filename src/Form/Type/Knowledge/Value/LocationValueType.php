<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Utils\LocalisableUtils;

class LocationValueType extends AbstractValueType {

	private $localisableUtils;

	public function __construct(LocalisableUtils $localisableUtils) {
		$this->localisableUtils = $localisableUtils;
	}

	/////

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('location', TextareaType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				if (!is_null($value)) {
					$this->localisableUtils->geocodeLocation($value);
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Location',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_location';
	}

}
