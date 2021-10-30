<?php

namespace App\Form\Type\Message;

use Doctrine\Persistence\ManagerRegistry;
use App\Form\DataTransformer\UsersToUsernamesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\PicturesToIdsTransformer;

class NewThreadMessageType extends AbstractType {

	private $om;

	public function __construct(ManagerRegistry $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add($builder
				->create('recipients', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden2' ) ))
				->addModelTransformer(new UsersToUsernamesTransformer($this->om)))
			->add('subject')
			->add('body', TextareaType::class)
			->add($builder
				->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PicturesToIdsTransformer($this->om)))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Form\Model\NewThreadMessage'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_message_newthread';
	}

}
