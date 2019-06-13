<?php

namespace Ladb\CoreBundle\Form\Type\Find\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;

class EventType extends AbstractType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add($builder
					->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('location')
			->add('startDate', DateType::class, array( 'html5' => false, 'format' => 'dd/MM/yyyy', 'widget' => 'single_text' ))
			->add('startTime', TimeType::class, array( 'html5' => false, 'widget' => 'single_text' ))
			->add('endDate', DateType::class, array( 'html5' => false, 'format' => 'dd/MM/yyyy', 'widget' => 'single_text' ))
			->add('endTime', TimeType::class, array( 'html5' => false, 'widget' => 'single_text' ))
			->add('url')
			->add('cancelled')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Find\Content\Event',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_find_content_event';
	}

}
