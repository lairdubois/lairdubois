<?php

namespace Ladb\CoreBundle\Form\Type\Block;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlockType extends AbstractType {

	protected $dataClass = 'Ladb\CoreBundle\Entity\Block\AbstractBlock';

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('_type', HiddenType::class, array(
				'data'   => $this->getBlockPrefix(),
				'mapped' => false
			))
			->add('sortIndex', HiddenType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver
			->setDefaults(array(
				'data_class'  => $this->dataClass,
				'model_class' => $this->dataClass,
			))
			->setRequired(array(
				'em',
			))
			->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager')
		;
	}

}
