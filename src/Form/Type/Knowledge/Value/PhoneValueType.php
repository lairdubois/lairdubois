<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('rawPhoneNumber')
			->add('country', \Symfony\Component\Form\Extension\Core\Type\CountryType::class)
		;

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$value = $event->getForm()->getData();

				$rawPhoneNumber = $value->getRawPhoneNumber();
				$country = $value->getCountry();

				$phoneUtil = PhoneNumberUtil::getInstance();
				try {

					$phoneNumber = $phoneUtil->parse($rawPhoneNumber, $country);
					$formatedPhoneNumber = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
					$value->setData($formatedPhoneNumber);

				} catch (NumberParseException $e) {
				}
			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Phone',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_phone';
	}

}
