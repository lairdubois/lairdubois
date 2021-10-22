<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('rawLanguage', LanguageType::class)
		;

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$value = $event->getForm()->getData();

				$rawLanguage = $value->getRawLanguage();

				$value->setData(Intl::getLanguageBundle()->getLanguageName($rawLanguage));

			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Language',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_language';
	}

}
