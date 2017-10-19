<?php

namespace Ladb\CoreBundle\Form\Type\Youtook;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ladb\CoreBundle\Form\DataTransformer\PictureToIdTransformer;

class EditTookType extends AbstractType {

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
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Youtook\Took',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_youtook_edit_took';
	}

}
