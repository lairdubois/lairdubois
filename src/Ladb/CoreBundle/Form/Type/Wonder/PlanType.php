<?php

namespace Ladb\CoreBundle\Form\Type\Wonder;

use Ladb\CoreBundle\Form\DataTransformer\Knowledge\SchoolsToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Qa\QuestionsToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\ResourcesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToLabelsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use Ladb\CoreBundle\Form\Type\Core\LicenseType;

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
			'data_class' => 'Ladb\CoreBundle\Entity\Wonder\Plan'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_wonder_plan';
	}

}
