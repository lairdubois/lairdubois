<?php

namespace App\Form\Type\Core;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('version',ChoiceType::class, array(
				'choices'  => array(
					'3.0' => '3.0',
					'4.0' => '4.0',
				),
			))
			->add('allowDerivs')
			->add('shareAlike')
			->add('allowCommercial')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Core\License'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_license';
	}

}
