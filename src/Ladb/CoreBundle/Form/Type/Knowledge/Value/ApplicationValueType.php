<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('name', TextType::class)
			->add('isAddOn', CheckboxType::class)
			->add('hostSoftware', TextType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				$value->setName(ucfirst($value->getName()));
				$value->setHostSoftware(ucfirst($value->getHostSoftware()));
				if (!$value->getIsAddOn()) {
					$value->setHostSoftware(null);
					$value->setData($value->getName());
				} else {
					$value->setData($value->getName().','.$value->getHostSoftware());
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\Application',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_application';
	}

}
