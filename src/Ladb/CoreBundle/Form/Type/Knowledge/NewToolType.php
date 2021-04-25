<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge;

use Ladb\CoreBundle\Form\Type\Knowledge\Value\TextValueType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ladb\CoreBundle\Form\Type\Knowledge\Value\PictureValueType;

class NewToolType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('nameValue', TextValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
				),
				'validation_groups' => array( 'mandatory' )
			))
			->add('photoValue', PictureValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
				),
				'validation_groups' => array( 'mandatory' )
			))
			->add('productNameValue', TextValueType::class, array(
				'choices'         => null,
				'dataConstraints' => null,
				'constraints'     => array(
					new \Symfony\Component\Validator\Constraints\Valid(),
				)
			))
			->add('brandValue', TextValueType::class, array(
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
			'data_class' => 'Ladb\CoreBundle\Form\Model\NewTool',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_newtool';
	}

}
