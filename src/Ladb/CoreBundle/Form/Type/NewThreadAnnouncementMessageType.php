<?php

namespace Ladb\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewThreadAnnouncementMessageType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('subject')
			->add('body', TextareaType::class);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Form\Model\NewThreadAnnouncementMessage'
		));
	}

	public function getBlockPrefix() {
		return 'ladb_newthreadannouncement';
	}

}
