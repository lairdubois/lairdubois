<?php

namespace App\Form\Type\Wonder;

use App\Form\DataTransformer\Knowledge\SchoolsToIdsTransformer;
use App\Form\DataTransformer\Qa\QuestionsToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Form\DataTransformer\PicturesToIdsTransformer;
use App\Form\DataTransformer\ResourcesToIdsTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use App\Form\Type\Core\LicenseType;

class PlanType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
					->create('resources', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new ResourcesToIdsTransformer($this->om))
			)
			->add($builder
				->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('sketchup3DWarehouseUrl', TextType::class)
			->add('a360Url', TextType::class)
			->add('body', TextareaType::class)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('inspirations', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new PlansToIdsTransformer($this->om))
			)
			->add($builder
				->create('questions', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new QuestionsToIdsTransformer($this->om))
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
			'data_class' => 'App\Entity\Wonder\Plan'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_plan';
	}

}
