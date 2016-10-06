<?php

namespace Ladb\CoreBundle\Form\Type\Funding;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChargeType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('label')
			->add('dutyFreeAmount')
			->add('amount')
			->add('isRecurrent')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Funding\Charge'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_funding_charge';
	}

}
