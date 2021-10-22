<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SoftwareIdentityValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('name', TextType::class)
			->add('isAddOn', CheckboxType::class)
			->add('hostSoftwareName', TextType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				$value->setName(ucfirst($value->getName()));
				if (!$value->getIsAddOn()) {
					$value->setHostSoftwareName(null);
					$value->setData($value->getName());
				} else {
					$value->setHostSoftwareName(ucfirst($value->getHostSoftwareName()));
					$value->setData($value->getName().','.$value->getHostSoftwareName());
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\SoftwareIdentity',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_software_identity';
	}

}
