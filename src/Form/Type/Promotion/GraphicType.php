<?php

namespace App\Form\Type\Promotion;

use App\Form\DataTransformer\ResourceToIdTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\Type\Core\LicenseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GraphicType extends AbstractType {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
				->create('resource', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new ResourceToIdTransformer($this->om))
			)
			->add('body', TextareaType::class)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
			->add('license', LicenseType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Promotion\Graphic'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_promotion_graphic';
	}

}
