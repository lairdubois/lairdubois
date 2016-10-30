<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerValueType extends AbstractValueType {

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
					'choices' => array_flip($options['choices']),
					'expanded' => true,
				))
			;
		}
	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\Integer',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_integer';
	}

}
