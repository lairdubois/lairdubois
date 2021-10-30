<?php

namespace App\Form\Type\Collection;

use App\Form\DataTransformer\PictureToIdTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends AbstractType {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
				->create('mainPicture', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PictureToIdTransformer($this->om))
			)
			->add('body', TextareaType::class)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Collection\Collection',
			'validation_groups' => function (FormInterface $form) {
				$collection = $form->getData();
				if ($collection->getIsPublic()) {
					return array('Default', 'public');
				}
				return array('Default');
			},
		));
	}

	public function getBlockPrefix() {
		return 'ladb_collection_collection';
	}

}
