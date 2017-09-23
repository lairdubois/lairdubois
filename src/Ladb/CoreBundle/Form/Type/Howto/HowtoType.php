<?php

namespace Ladb\CoreBundle\Form\Type\Howto;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PictureToIdTransformer;
use Ladb\CoreBundle\Form\DataTransformer\ArticlesToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\TagsToLabelsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\PlansToIdsTransformer;
use Ladb\CoreBundle\Form\DataTransformer\ProvidersToIdsTransformer;
use Ladb\CoreBundle\Form\Type\LicenseType;

class HowtoType extends AbstractType {

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
			->add('body')
			->add('isWorkInProgress')
			->add($builder
					->create('articles', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden') ))
					->addModelTransformer(new ArticlesToIdsTransformer($this->om))
			)
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
			->add($builder
				->create('plans', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new PlansToIdsTransformer($this->om))
			)
			->add($builder
				->create('providers', HiddenType::class, array( 'required' => false ))
				->addModelTransformer(new ProvidersToIdsTransformer($this->om))
			)
			->add('license', LicenseType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Howto\Howto'
		));
	}

	public function getName() {
		return 'ladb_howto_howto';
	}

}
