<?php

namespace App\Form\Type\Core;

use App\Form\Type\PolyCollectionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackType extends AbstractType {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title', TextType::class)
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
					'om' => $this->om,
				),
				'constraints'     => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Core\Feedback',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_core_feedback';
	}

}
