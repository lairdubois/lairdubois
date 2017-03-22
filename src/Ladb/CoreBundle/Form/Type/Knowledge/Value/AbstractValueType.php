<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ladb\CoreBundle\Entity\Knowledge\Value\BaseValue;

abstract class AbstractValueType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('legend', TextareaType::class)
			->add('sourceType', ChoiceType::class, array(
				'placeholder' => 'Choisissez un type de source',
				'choices'     => array_flip(BaseValue::$SOURCE_TYPES),
			))
			->add('source', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setRequired(array(
			'choices',
			'dataConstraints',
		));
	}

}
