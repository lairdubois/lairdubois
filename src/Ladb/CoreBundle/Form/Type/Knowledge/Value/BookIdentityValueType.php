<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookIdentityValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('work', TextType::class)
			->add('isVolume', CheckboxType::class)
			->add('volume', TextType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$value = $event->getData();
				$value->setWork(ucfirst($value->getWork()));
				if (!$value->getIsVolume()) {
					$value->setVolume(null);
					$value->setData($value->getWork());
				} else {
					$value->setVolume(ucfirst($value->getVolume()));
					$value->setData($value->getWork().','.$value->getVolume());
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\BookIdentity',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_book_identity';
	}

}
