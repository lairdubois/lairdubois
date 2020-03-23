<?php

namespace Ladb\CoreBundle\Form\Type\Core;

use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\Type\PolyCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title', TextType::class)
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'           => array(
					\Ladb\CoreBundle\Form\Type\Block\TextBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\GalleryBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'       => true,
				'allow_delete'    => true,
				'by_reference'    => false,
				'options'         => array(
					'em' => $this->om,
				),
				'constraints'     => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Core\Feedback',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_core_feedback';
	}

}
