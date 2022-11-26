<?php

namespace Ladb\CoreBundle\Form\Type\Offer;

use Ladb\CoreBundle\Entity\Offer\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToLabelsTransformer;
use Ladb\CoreBundle\Form\Type\PolyCollectionType;

class OfferType extends AbstractType {

	private $om;
	private $localisableUtils;

	public function __construct(ObjectManager $om, LocalisableUtils $localisableUtils) {
		$this->om = $om;
		$this->localisableUtils = $localisableUtils;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
				->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'        => array(
					\Ladb\CoreBundle\Form\Type\Block\TextBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\GalleryBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'    => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options'      => array(
					'em' => $this->om,
				),
				'constraints'  => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('kind', ChoiceType::class, array(
				'choices'  => array_flip(array(
					Offer::KIND_OFFER   => 'Offre - <small class="text-muted">Je propose, je donne ou je vends.</small>',
					Offer::KIND_REQUEST => 'Demande - <small class="text-muted">Je cherche.</small>',
				)),
				'expanded' => true,
			))
			->add('category', ChoiceType::class, array(
				'choices'  => array_flip(array(
                    Offer::CATEGORY_JOB         => 'Emploi',
                    Offer::CATEGORY_TOOL        => 'Matériel',
                    Offer::CATEGORY_MATERIAL    => 'Matière',
                    Offer::CATEGORY_SERVICE     => 'Service',
                    Offer::CATEGORY_WORKSHOP    => 'Local d\'activité',
                    Offer::CATEGORY_OTHER       => 'Autre',
                )),
				'expanded' => true,
			))
			->add('rawPrice')
			->add('currency', CurrencyType::class)
			->add('priceSuffix', TextType::class)
			->add('location')
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$offer = $event->getForm()->getData();

				// Try to geocode location
				$this->localisableUtils->geocodeLocation($offer);

				// Format price
				$rawPrice = $offer->getRawPrice();
				$currency = $offer->getCurrency();

				if ($offer->getKind() == Offer::KIND_OFFER && $offer->getCategory() != Offer::CATEGORY_JOB) {
					if ($rawPrice > 0) {
						$formatter = new \NumberFormatter('fr_FR',  \NumberFormatter::CURRENCY);
						$offer->setPrice(str_replace(',00', '', $formatter->formatCurrency($rawPrice, $currency)));
					} else {
						$offer->setPrice('Gratuit');
					}
				} else {
					$offer->setPrice('');
					$offer->setRawPrice(0);
				}

			}
		);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Offer\Offer',
			'validation_groups' => function (FormInterface $form) {
				$offer = $form->getData();
				switch ($offer->getKind()) {

					case Offer::KIND_OFFER:
						return array('Default', 'offer');

					case Offer::KIND_REQUEST:
						return array('Default', 'request');

				}
				return array('Default');
			},
		));
	}

	public function getBlockPrefix() {
		return 'ladb_offer_offer';
	}

}
