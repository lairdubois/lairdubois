<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DecimalValueType extends AbstractValueType {

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

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$decimal = $event->getForm()->getData();
				$decimal->setData(str_replace(',', '.', $decimal->getData()));
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\Decimal',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_integer';
	}

}
