<?php

namespace App\Form\Type\Knowledge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\Type\Knowledge\Value\SoftwareIdentityValueType;
use App\Form\Type\Knowledge\Value\PictureValueType;

class NewSoftwareType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('identityValue', SoftwareIdentityValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
					new \App\Validator\Constraints\UniqueSoftware(),
				)
			))
			->add('iconValue', PictureValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
				)
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Form\Model\NewSoftware',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_newsoftware';
	}

}
