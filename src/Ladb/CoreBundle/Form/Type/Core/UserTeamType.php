<?php

namespace Ladb\CoreBundle\Form\Type\Core;

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

class UserTeamType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('username', TextType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Core\User',
			'constraints' => new Valid(),
			'validation_groups' => array( 'Default', 'Registration' ),
		));
	}

	public function getBlockPrefix() {
		return 'ladb_userteam';
	}

}
