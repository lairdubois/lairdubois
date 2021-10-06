<?php

namespace App\Form\Type\Knowledge\School;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestimonialType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('fromYear')
			->add('toYear')
			->add('diploma')
			->add('body', TextareaType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\School\Testimonial',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_school_testimonial';
	}

}
