<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('data', UrlType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Url',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_url';
	}

}
