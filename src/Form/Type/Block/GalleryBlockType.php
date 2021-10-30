<?php

namespace App\Form\Type\Block;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Form\DataTransformer\PicturesToIdsTransformer;

class GalleryBlockType extends BlockType {

	protected $dataClass = 'App\Entity\Core\Block\Gallery';

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add($builder
					->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PicturesToIdsTransformer($options['om']))
			)
		;
	}

	public function getBlockPrefix() {
		return 'ladb_block_gallery';
	}

}
