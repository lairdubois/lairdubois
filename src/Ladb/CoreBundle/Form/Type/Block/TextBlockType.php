<?php

namespace Ladb\CoreBundle\Form\Type\Block;

use Symfony\Component\Form\FormBuilderInterface;

class TextBlockType extends BlockType {

	protected $dataClass = 'Ladb\CoreBundle\Entity\Core\Block\Text';

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('body')
		;
	}

	public function getBlockPrefix() {
		return 'ladb_block_text';
	}

}
