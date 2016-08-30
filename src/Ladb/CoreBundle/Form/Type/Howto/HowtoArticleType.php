<?php

namespace Ladb\CoreBundle\Form\Type\Howto;

use Ladb\CoreBundle\Form\Type\PolyCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;

class HowtoArticleType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
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
        ;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Howto\Article',
			'cascade_validation' => true,
		));
	}

	public function getBlockPrefix() {
		return 'ladb_howto_howtoarticle';
	}

}
