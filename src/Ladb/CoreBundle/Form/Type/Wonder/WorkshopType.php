<?php

namespace Ladb\CoreBundle\Form\Type\Wonder;

use Ladb\CoreBundle\Form\Type\PolyCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Howto\HowtosToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToNamesTransformer;
use Ladb\CoreBundle\Form\Type\LicenseType;
use Ladb\CoreBundle\Utils\LocalisableUtils;

class WorkshopType extends AbstractType {

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
			->add('location')
			->add('area')
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToNamesTransformer($this->om))
			)
			->add($builder
				->create('plans', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new PlansToIdsTransformer($this->om))
			)
			->add($builder
				->create('howtos', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new HowtosToIdsTransformer($this->om))
			)
			->add('license', LicenseType::class)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$workshop = $event->getForm()->getData();
				$this->localisableUtils->geocodeLocation($workshop);
			}
		);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Wonder\Workshop',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_workshop';
	}

}
