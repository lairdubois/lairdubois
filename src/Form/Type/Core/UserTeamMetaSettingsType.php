<?php

namespace App\Form\Type\Core;

use App\Form\DataTransformer\Input\SkillsToLabelsTransformer;
use App\Form\DataTransformer\PictureToIdTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class UserTeamMetaSettingsType extends AbstractType {

	private $om;

	public function __construct(EntityManagerInterface $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add($builder
					->create('banner', HiddenType::class)
					->addModelTransformer(new PictureToIdTransformer($this->om))
			)
			->add('biography', BiographyType::class)
			->add($builder
				->create('skills', HiddenType::class, array('required' => false))
				->addModelTransformer(new SkillsToLabelsTransformer($this->om))
			)
			->add('website')
			->add('facebook')
			->add('twitter')
			->add('youtube')
			->add('vimeo')
			->add('dailymotion')
			->add('pinterest')
			->add('instagram')
			->add('requestEnabled')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Core\UserMeta',
			'constraints' => new Valid(),
			'validation_groups' => array( 'Default', 'settings' ),
		));
	}

	public function getBlockPrefix() {
		return 'ladb_userteammetasettings';
	}

}
