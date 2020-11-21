<?php

namespace Ladb\CoreBundle\Form\Type;

use Ladb\CoreBundle\Entity\Core\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PictureToIdTransformer;
use Ladb\CoreBundle\Form\DataTransformer\Input\SkillsToLabelsTransformer;
use Symfony\Component\Validator\Constraints\Valid;

class UserTeamSettingsType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add($builder
					->create('avatar', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PictureToIdTransformer($this->om))
			)
			->add('username', TextType::class)
			->add('displayname')
			->add('fullname')
			->add('accountType', ChoiceType::class, array(
				'choices' => array_flip(array(
					User::ACCOUNT_TYPE_NONE     => 'Un collectif de curieux-ses',
					User::ACCOUNT_TYPE_ASSO     => 'Une association&nbsp;<i class="ladb-icon-badge-asso"></i>',
					User::ACCOUNT_TYPE_HOBBYIST => 'Un collectif de passionné-e-s du travail du bois&nbsp;<i class="ladb-icon-badge-hobbyist"></i>',
					User::ACCOUNT_TYPE_PRO      => 'Un collectif de professionnel-le-s des métiers du bois&nbsp;<i class="ladb-icon-badge-pro"></i>',
					User::ACCOUNT_TYPE_BRAND    => 'Une marque&nbsp;<i class="ladb-icon-badge-brand"></i>',
				)),
				'expanded' => true,
			))
			->add('location')
			->add($builder
				->create('meta', UserTeamMetaSettingsType::class)
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Core\User',
			'constraints' => new Valid(),
			'validation_groups' => array( 'Default', 'settings' ),
		));
	}

	public function getBlockPrefix() {
		return 'ladb_userteamsettings';
	}

}
