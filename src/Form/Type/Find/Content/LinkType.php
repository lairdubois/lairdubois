<?php

namespace App\Form\Type\Find\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\DataTransformer\PictureToIdTransformer;

class LinkType extends AbstractType {

	private $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('url')
			->add($builder
					->create('thumbnail', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PictureToIdTransformer($this->om))
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Find\Content\Link',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_find_content_link';
	}

}
