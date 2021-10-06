<?php

namespace App\Form\Type\Blog;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\PictureToIdTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Entity\Blog\Post;
use App\Form\Type\PolyCollectionType;

class PostType extends AbstractType {

	private $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add($builder
					->create('mainPicture', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PictureToIdTransformer($this->om))
			)
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
			->add('hasToc')
			->add('highlightLevel', ChoiceType::class, array(
				'choices' => array_flip(array(
					Post::HIGHLIGHT_LEVEL_NONE => 'Aucune mise en avant',
					Post::HIGHLIGHT_LEVEL_USER_ONLY => 'Mise en avant pour les utilisateurs connectés seulement',
					Post::HIGHLIGHT_LEVEL_ALL => 'Mise en avant pour tout le monde')),
				'expanded' => true,
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Blog\Post',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_blog_post';
	}

}
