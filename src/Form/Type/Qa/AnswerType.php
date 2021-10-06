<?php

namespace App\Form\Type\Qa;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Form\Type\PolyCollectionType;

class AnswerType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
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
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Qa\Answer',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_qa_answer';
	}

}
