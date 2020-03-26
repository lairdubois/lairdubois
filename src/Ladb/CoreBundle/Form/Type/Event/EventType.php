<?php

namespace Ladb\CoreBundle\Form\Type\Event;

use Ladb\CoreBundle\Form\DataTransformer\PicturesToIdsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\TagsToLabelsTransformer;
use Ladb\CoreBundle\Form\Type\PolyCollectionType;
use Ladb\CoreBundle\Entity\Event\Event;
use Ladb\CoreBundle\Utils\VideoHostingUtils;
use Ladb\CoreBundle\Utils\LocalisableUtils;
use Ladb\CoreBundle\Utils\LinkUtils;

class EventType extends AbstractType {

	private $om;
	private $localisableUtils;
	private $linkUtils;

	public function __construct(ObjectManager $om, LocalisableUtils $localisableUtils, LinkUtils $linkUtils) {
		$this->om = $om;
		$this->localisableUtils = $localisableUtils;
		$this->linkUtils = $linkUtils;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('title')
			->add('bodyBlocks', PolyCollectionType::class, array(
				'types'        => array(
					\Ladb\CoreBundle\Form\Type\Block\TextBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\GalleryBlockType::class,
					\Ladb\CoreBundle\Form\Type\Block\VideoBlockType::class,
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
			'data_class' => 'Ladb\CoreBundle\Entity\Event\Event',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_event_event';
	}

}
