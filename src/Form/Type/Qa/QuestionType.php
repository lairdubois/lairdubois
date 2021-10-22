<?php

namespace App\Form\Type\Qa;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\Type\PolyCollectionType;

class QuestionType extends AbstractType {

	private $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'           => array(
					\App\Form\Type\Block\TextBlockType::class,
					\App\Form\Type\Block\GalleryBlockType::class,
					\App\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'       => true,
				'allow_delete'    => true,
				'by_reference'    => false,
				'options'         => array(
					'em' => $this->om,
				),
				'constraints'     => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add($builder
					->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Qa\Question',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_qa_question';
	}

}
