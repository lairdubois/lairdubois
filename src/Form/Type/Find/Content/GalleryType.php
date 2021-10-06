<?php

namespace App\Form\Type\Find\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Form\DataTransformer\PicturesToIdsTransformer;

class GalleryType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add($builder
					->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('location')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Find\Content\Gallery',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_find_content_gallery';
	}

}
