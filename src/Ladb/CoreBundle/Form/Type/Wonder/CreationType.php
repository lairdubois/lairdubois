<?php

namespace Ladb\CoreBundle\Form\Type\Wonder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\Input\WoodsToLabelsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Input\FinishesToLabelsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Input\ToolsToLabelsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\PlansToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\HowtosToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\CreationsToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToNamesTransformer;
use Ladb\CoreBundle\Form\Type\LicenseType;
use Ladb\CoreBundle\Form\Type\PolyCollectionType;

class CreationType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
				->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types' => array(
					\Ladb\CoreBundle\Form\Type\Block\TextBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\GalleryBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add' => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options' => array(
					'em' => $this->om,
				),
			))
			->add($builder
				->create('woods', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new WoodsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('tools', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new ToolsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('finishes', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new FinishesToLabelsTransformer($this->om))
			)
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
			->add($builder
				->create('inspirations', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new CreationsToIdsTransformer($this->om))
			)
			->add('license', LicenseType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class'         => 'Ladb\CoreBundle\Entity\Wonder\Creation',
			'cascade_validation' => true,
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_creation';
	}

}
