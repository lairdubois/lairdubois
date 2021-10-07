<?php

namespace App\Form\Type\Knowledge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\Type\Knowledge\Value\PictureValueType;
use App\Form\Type\Knowledge\Value\SignValueType;

class NewProviderType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('signValue', SignValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
					new \App\Validator\Constraints\UniqueProvider(),
				)
			))
			->add('logoValue', PictureValueType::class, array(
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
			'data_class' => 'App\Form\Model\NewProvider',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_newprovider';
	}

}