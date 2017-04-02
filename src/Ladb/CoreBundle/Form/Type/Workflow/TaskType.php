<?php

namespace Ladb\CoreBundle\Form\Type\Workflow;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\Workflow\LabelsToIdsTransformer;

class TaskType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title', TextType::class, array( 'label' => 'workflow.task.title' ))
			->add($builder
				->create('labels', TextType::class, array( 'label' => 'workflow.task.labels', 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new LabelsToIdsTransformer($this->om))
			)
			->add('estimatedDuration', TextType::class, array( 'label' => 'workflow.task.estimated_duration', 'attr' => array( 'data-type' => 'duration' ) ))
			->add('duration', TextType::class, array( 'label' => 'workflow.task.duration', 'attr' => array( 'data-type' => 'duration' ) ))
			->add('positionLeft', HiddenType::class)
			->add('positionTop', HiddenType::class)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setRequired(array(
			'label_choices',
		));
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Workflow\Task',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_workflow_task';
	}

}
