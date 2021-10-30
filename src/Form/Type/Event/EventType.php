<?php

namespace App\Form\Type\Event;

use App\Form\DataTransformer\PicturesToIdsTransformer;
use App\Form\DataTransformer\TagsToLabelsTransformer;
use App\Form\Type\PolyCollectionType;
use App\Utils\LocalisableUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType {

	private $om;
	private $localisableUtils;

	public function __construct(EntityManagerInterface $om, LocalisableUtils $localisableUtils) {
		$this->om = $om;
		$this->localisableUtils = $localisableUtils;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'        => array(
					\App\Form\Type\Block\TextBlockType::class,
					\App\Form\Type\Block\GalleryBlockType::class,
					\App\Form\Type\Block\VideoBlockType::class,
				),
				'allow_add'    => true,
				'allow_delete' => true,
				'by_reference' => false,
				'options'      => array(
					'em' => $this->om,
				),
				'constraints'  => array(new \Symfony\Component\Validator\Constraints\Valid())
			))
			->add($builder
				->create('pictures', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new PicturesToIdsTransformer($this->om))
			)
			->add('location')
//			->add('startDate', DateType::class, array( 'html5' => false, 'format' => 'dd/MM/yyyy', 'widget' => 'single_text' ))
			->add('startDate', DateType::class, array( 'html5' => false, 'format' => 'dd/MM/yyyy', 'widget' => 'single_text' ))
			->add('startTime', TimeType::class, array( 'html5' => false, 'widget' => 'single_text' ))
			->add('endDate', DateType::class, array( 'html5' => false, 'format' => 'dd/MM/yyyy', 'widget' => 'single_text' ))
			->add('endTime', TimeType::class, array( 'html5' => false, 'widget' => 'single_text' ))
			->add('url')
			->add('online')
			->add('cancelled')
			->add($builder
				->create('tags', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
				->addModelTransformer(new TagsToLabelsTransformer($this->om))
			)
		;

		$builder->addEventListener(
			FormEvents::SUBMIT,
			function(FormEvent $event) {
				$event = $event->getForm()->getData();

				$this->localisableUtils->geocodeLocation($event);

				$startAt = null;
				$endAt = null;
				if (!is_null($event->getStartDate())) {
					$startAt = clone $event->getStartDate();
					if (!is_null($event->getStartTime())) {
						$startAt->add(new \DateInterval('PT'.$event->getStartTime()->format('G').'H'.$event->getStartTime()->format('i').'M'));
					}

					if (!is_null($event->getEndDate())) {
						$endAt = clone $event->getEndDate();
						if (!is_null($event->getEndTime())) {
							$endAt->add(new \DateInterval('PT'.$event->getEndTime()->format('G').'H'.$event->getEndTime()->format('i').'M'));
						} else {
							$endAt->add(new \DateInterval('P1D'));
						}
					} else {
						$endAt = clone $event->getStartDate();
						$endAt->add(new \DateInterval('P1D'));
					}
				}

				$event->setStartAt($startAt);
				$event->setEndAt($endAt);

			}
		);

	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Event\Event',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_event_event';
	}

}
