<?php

namespace Ladb\CoreBundle\Form\Type\Workflow;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ladb\CoreBundle\Form\DataTransformer\Workflow\LabelsToNamesAndColorsTransformer;

class PartType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('number', TextType::class, array( 'label' => 'workflow.part.number' ))
			->add('name', TextType::class, array( 'label' => 'workflow.part.name' ))
			->add('count', TextType::class, array( 'label' => 'workflow.part.count' ))
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Workflow\Part',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_workflow_part';
	}

}
