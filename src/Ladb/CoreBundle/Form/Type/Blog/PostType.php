<?php

namespace Ladb\CoreBundle\Form\Type\Blog;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PictureToIdTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToNamesTransformer;
use Ladb\CoreBundle\Entity\Blog\Post;
use Ladb\CoreBundle\Form\Type\PolyCollectionType;

class PostType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
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
				'types' => array(
					\Ladb\CoreBundle\Form\Type\Block\TextBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\GalleryBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add' => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options' => array(
					'em' => $this->om,
				),
			))
			->add($builder
					->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new TagsToNamesTransformer($this->om))
			)
			->add('highlightLevel', ChoiceType::class, array(
				'choices' => array(
					Post::HIGHLIGHT_LEVEL_NONE => 'Aucune mise en avant',
					Post::HIGHLIGHT_LEVEL_USER_ONLY => 'Mise en avant pour les utilisateurs connectés seulement',
					Post::HIGHLIGHT_LEVEL_ALL => 'Mise en avant pour tout le monde'),
				'expanded' => true,
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class'         => 'Ladb\CoreBundle\Entity\Blog\Post',
			'cascade_validation' => true
		));
	}

	public function getBlockPrefix() {
		return 'ladb_blog_post';
	}

}
