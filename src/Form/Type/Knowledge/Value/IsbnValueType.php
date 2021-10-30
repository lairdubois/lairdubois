<?php

namespace App\Form\Type\Knowledge\Value;

use Biblys\Isbn\Isbn;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsbnValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('rawIsbn', TextType::class)
		;

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$value = $event->getForm()->getData();

				$rawIsbn = $value->getRawIsbn();

				if (Isbn::isParsable($rawIsbn)) {

					$formatedIsbn = Isbn::convertToIsbn13($rawIsbn);
					$value->setData($formatedIsbn);

				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Isbn',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_isbn';
	}

}
