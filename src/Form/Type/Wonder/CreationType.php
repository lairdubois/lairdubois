<?php

namespace App\Form\Type\Wonder;

use App\Form\DataTransformer\Howto\HowtosToIdsTransformer;
use App\Form\DataTransformer\Input\FinishesToLabelsTransformer;
use App\Form\DataTransformer\Input\HardwaresToLabelsTransformer;
use App\Form\DataTransformer\Input\ToolsToLabelsTransformer;
use App\Form\DataTransformer\Input\WoodsToLabelsTransformer;
use App\Form\DataTransformer\Knowledge\ProvidersToIdsTransformer;
use App\Form\DataTransformer\Knowledge\SchoolsToIdsTransformer;
use App\Form\DataTransformer\PicturesToIdsTransformer;
use App\Form\DataTransformer\Qa\QuestionsToIdsTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\DataTransformer\Wonder\CreationsToIdsTransformer;
use App\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use App\Form\DataTransformer\Workflow\WorkflowsToIdsTransformer;
use App\Form\Type\Core\LicenseType;
use App\Form\Type\PolyCollectionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreationType extends AbstractType {

	private $om;

	public function __construct(EntityManagerInterface $om) {
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
				->create('hardwares', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new HardwaresToLabelsTransformer($this->om))
			)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('inspirations', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new CreationsToIdsTransformer($this->om))
			)
			->add($builder
				->create('questions', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new QuestionsToIdsTransformer($this->om))
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
			->add($builder
				->create('providers', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new ProvidersToIdsTransformer($this->om))
			)
			->add($builder
				->create('schools', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new SchoolsToIdsTransformer($this->om))
			)
			->add('license', LicenseType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Wonder\Creation',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_creation';
	}

}
