<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Validator\Constraints\OneThing;
use Ladb\CoreBundle\Form\Type\Knowledge\Value\PictureValueType;
use Ladb\CoreBundle\Form\Type\Knowledge\Value\TextValueType;

class NewWoodType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('nameValue', TextValueType::class, array('choices' => null, 'dataConstraints' => array( new OneThing(array('message' => 'N\'indiquez qu\'un seul Nom français' )) ), 'constraints' => new \Ladb\CoreBundle\Validator\Constraints\UniqueWood() ))
			->add('grainValue', PictureValueType::class, array('choices' => null, 'dataConstraints' => null ))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class'         => 'Ladb\CoreBundle\Form\Model\NewWood',
			'cascade_validation' => true
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_newwood';
	}

}
