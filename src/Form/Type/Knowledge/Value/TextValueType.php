<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		if (is_null($options['choices'])) {
			$builder
				->add('data', TextType::class, array(
					'constraints' => $options['dataConstraints'],
				))
			;
		} else {
			$builder
				->add('data', ChoiceType::class, array(
					'choices' => $options['choices'],
					'expanded' => true,
				))
			;
		}

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$value = $event->getForm()->getData();
				$value->setData(ucfirst($value->getData()));
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Text',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_text';
	}

}
