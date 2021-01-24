<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToolIdentityValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('name', TextType::class)
			->add('isProduct', CheckboxType::class)
			->add('productName', TextType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				$value->setName(ucfirst($value->getName()));
				if (!$value->getIsProduct()) {
					$value->setProductName(null);
					$value->setData($value->getName());
				} else {
					$value->setProductName(ucfirst($value->getProductName()));
					$value->setData($value->getName().','.$value->getProductName());
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\ToolIdentity',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_tool_identity';
	}

}
