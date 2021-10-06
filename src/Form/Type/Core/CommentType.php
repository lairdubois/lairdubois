<?php

namespace App\Form\Type\Core;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ObjectManager;
use App\Form\DataTransformer\PicturesToIdsTransformer;

class CommentType extends AbstractType {

	const DEFAULT_BLOCK_PREFIX = 'ladb_comment';

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('body', TextareaType::class)
			->add($builder
					->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PicturesToIdsTransformer($this->om)))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Core\Comment',
		));
	}

	public function getBlockPrefix() {
		return self::DEFAULT_BLOCK_PREFIX;
	}

}
