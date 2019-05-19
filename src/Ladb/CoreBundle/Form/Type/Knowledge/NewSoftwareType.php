<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\Type\Knowledge\Value\ApplicationValueType;
use Ladb\CoreBundle\Form\Type\Knowledge\Value\PictureValueType;

class NewSoftwareType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('applicationValue', ApplicationValueType::class, array(
				'choices' => null,
				'dataConstraints' => null,
				'constraints' => array( new \Symfony\Component\Validator\Constraints\Valid(), new \Ladb\CoreBundle\Validator\Constraints\UniqueSoftware() )
			))
			->add('iconValue', PictureValueType::class, array(
				'choices' => null,
				'dataConstraints' => null,
				'constraints' => array( new \Symfony\Component\Validator\Constraints\Valid() )
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Form\Model\NewSoftware',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_newsoftware';
	}

}
