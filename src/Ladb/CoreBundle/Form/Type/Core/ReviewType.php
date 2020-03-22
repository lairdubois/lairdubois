<?php

namespace Ladb\CoreBundle\Form\Type\Core;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title', TextType::class)
			->add('rating', ChoiceType::class, array(
				'choices' => array_flip(array(
					0 => '',
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
					5 => 5
				)),
			))
			->add('body', TextareaType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Core\Review',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_core_review';
	}

}
