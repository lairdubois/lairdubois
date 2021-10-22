<?php

namespace Ladb\CoreBundle\Form\Type\Knowledge\Value;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;
use Ladb\CoreBundle\Form\DataTransformer\ResourceToIdTransformer;

class PdfValueType extends AbstractValueType {

	private $om;

	public function __construct(ObjectManager $om) {
		$this->om = $om;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add($builder
					->create('data', TextType::class, array( 'attr' => array( 'class' => 'ladb-pseudo-hidden' ) ))
					->addModelTransformer(new ResourceToIdTransformer($this->om))
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		parent::configureOptions($resolver);
		$resolver->setDefaults(array(
			'data_class' => 'Ladb\CoreBundle\Entity\Knowledge\Value\Pdf',
		));
	}

	public function getBlockPrefix() {
		return 'ladb_knowledge_value_pdf';
	}

}
