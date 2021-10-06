<?php

namespace App\Form\Type\Knowledge\Value;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceValueType extends AbstractValueType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('rawPrice')
			->add('currency', \Symfony\Component\Form\Extension\Core\Type\CurrencyType::class)
		;

		$builder->addEventListener(
			FormEvents::POST_SUBMIT,
			function(FormEvent $event) {
				$value = $event->getForm()->getData();

				$rawPrice = $value->getRawPrice();
				$currency = $value->getCurrency();

				$formatter = new \NumberFormatter('fr_FR',  \NumberFormatter::CURRENCY);
				$value->setData($formatter->formatCurrency($rawPrice, $currency));

			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Knowledge\Value\Price',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_price';
	}

}
