<?php

/*
 * (c) Infinite Networks <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\EventListener\PolyCollectionResizeFormListener;

/**
 * A collection type that will take an array of other form types
 * to use for each of the classes in an inheritance tree.
 *
 * The collection allows you to use the form component to manipulate
 * objects that have a common parent, like Doctrine's single or
 * multi table inheritance strategies by registering different
 * types for each class in the inheritance tree.
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 */
class PolyCollectionType extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$prototypes = $this->buildPrototypes($builder, $options);
		if ($options['allow_add'] && $options['prototype']) {
			$builder->setAttribute('prototypes', $prototypes);
		}

		$resizeListener = new PolyCollectionResizeFormListener(
			$prototypes,
			$options['options'],
			$options['allow_add'],
			$options['allow_delete'],
			$options['type_name']
		);

		$builder->addEventSubscriber($resizeListener);
	}

	/**
	 * Builds prototypes for each of the form types used for the collection.
	 *
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array                                        $options
	 *
	 * @return array
	 */
	protected function buildPrototypes(FormBuilderInterface $builder, array $options)
	{
		$prototypes = array();
		foreach ($options['types'] as $type) {
			$key = StringUtil::fqcnToBlockPrefix($type);
			$prototype = $this->buildPrototype(
				$builder,
				$options['prototype_name'],
				$type,
				$options['options']
			);
			$prototypes[$key] = $prototype->getForm();
		}

		return $prototypes;
	}

	/**
	 * Builds an individual prototype.
	 *
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param string                                       $name
	 * @param string|FormTypeInterface                     $type
	 * @param array                                        $options
	 *
	 * @return \Symfony\Component\Form\FormBuilderInterface
	 */
	protected function buildPrototype(FormBuilderInterface $builder, $name, $type, array $options)
	{
		$prototype = $builder->create($name, $type, array_replace(array(
			'label' => $name . 'label__',
		), $options));

		return $prototype;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$view->vars['allow_add']    = $options['allow_add'];
		$view->vars['allow_delete'] = $options['allow_delete'];

		if ($form->getConfig()->hasAttribute('prototypes')) {
			$view->vars['prototypes'] = array_map(function (FormInterface $prototype) use ($view) {
				return $prototype->createView($view);
			}, $form->getConfig()->getAttribute('prototypes'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function finishView(FormView $view, FormInterface $form, array $options)
	{
		if ($form->getConfig()->hasAttribute('prototypes')) {
			$multiparts = array_filter(
				$view->vars['prototypes'],
				function (FormView $prototype) {
					return $prototype->vars['multipart'];
				}
			);

			if ($multiparts) {
				$view->vars['multipart'] = true;
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$optionsNormalizer = function (Options $options, $value) {
			$value['block_name'] = 'entry';

			return $value;
		};

		$resolver->setDefaults(array(
			'allow_add'      => false,
			'allow_delete'   => false,
			'prototype'      => true,
			'prototype_name' => '__name__',
			'type_name'      => '_type',
			'options'        => array(),
			'error_bubbling' => false,
		));

		$resolver->setRequired(array(
			'types'
		));

		$resolver->setAllowedTypes('types', 'array');

		$resolver->setNormalizer('options', $optionsNormalizer);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'ladb_polycollection';
	}
}
