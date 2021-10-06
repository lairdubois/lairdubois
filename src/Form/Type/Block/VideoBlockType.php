<?php

namespace App\Form\Type\Block;

use Symfony\Component\Form\FormBuilderInterface;

class VideoBlockType extends BlockType {

	protected $dataClass = 'App\Entity\Core\Block\Video';

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
			->add('url')
		;
	}

	public function getBlockPrefix() {
		return 'ladb_block_video';
	}

}
