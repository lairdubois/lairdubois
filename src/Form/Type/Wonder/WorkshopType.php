<?php

namespace App\Form\Type\Wonder;

use App\Form\DataTransformer\Howto\HowtosToIdsTransformer;
use App\Form\DataTransformer\PicturesToIdsTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use App\Form\DataTransformer\Workflow\WorkflowsToIdsTransformer;
use App\Form\Type\Core\LicenseType;
use App\Form\Type\PolyCollectionType;
use App\Utils\LocalisableUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopType extends AbstractType {

	private $om;
	private $localisableUtils;

	public function __construct(EntityManagerInterface $om, LocalisableUtils $localisableUtils) {
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
					\App\Form\Type\Block\TextBlockType::class,
					\App\Form\Type\Block\GalleryBlockType::class,
					\App\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'    => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options'      => array(
					'om' => $this->om,
				),
				'constraints'  => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add('location')
			->add('area')
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('plans', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new PlansToIdsTransformer($this->om))
			)
			->add($builder
				->create('howtos', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new HowtosToIdsTransformer($this->om))
			)
			->add($builder
				->create('workflows', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new WorkflowsToIdsTransformer($this->om))
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
			'data_class' => 'App\Entity\Wonder\Workshop',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_workshop';
	}

}
