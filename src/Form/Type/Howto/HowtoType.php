<?php

namespace App\Form\Type\Howto;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Howto\Howto;
use App\Form\DataTransformer\Qa\QuestionsToIdsTransformer;
use App\Form\DataTransformer\Knowledge\SchoolsToIdsTransformer;
use App\Form\DataTransformer\PictureToIdTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\DataTransformer\Howto\ArticlesToIdsTransformer;
use App\Form\DataTransformer\Wonder\PlansToIdsTransformer;
use App\Form\DataTransformer\Knowledge\ProvidersToIdsTransformer;
use App\Form\DataTransformer\Workflow\WorkflowsToIdsTransformer;
use App\Form\Type\Core\LicenseType;

class HowtoType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
            ->add($builder
                ->create('mainPicture', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
                ->addModelTransformer(new PictureToIdTransformer($this->om))
            )
			->add('kind', ChoiceType::class, array(
				'choices' => array_flip(array(
					Howto::KIND_NONE => 'Non définie',
					Howto::KIND_TUTORIAL => '<i class="ladb-icon-howto-tutorial"></i> Tuto - <small class="text-muted">Détails d\'une réalisation étape par étape</small>',
					Howto::KIND_TECHNICAL => '<i class="ladb-icon-howto-technical"></i> Technique - <small class="text-muted">Détails d\'une ou plusieurs techniques de mise en oeuvre</small>',
					Howto::KIND_DOCUMENTATION => '<i class="ladb-icon-howto-documentation"></i> Documentation - <small class="text-muted">Rédaction d\'une base documentaire sur un sujet donné</small>')),
				'expanded' => true,
			))
			->add('body')
			->add('isWorkInProgress')
			->add($builder
					->create('articles', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden') ))
					->addModelTransformer(new ArticlesToIdsTransformer($this->om))
			)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
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
			'data_class' => 'App\Entity\Howto\Howto'
		));
	}

	public function getName() {
		return 'ladb_howto_howto';
	}

}
