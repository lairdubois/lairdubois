<?php

namespace Ladb\CoreBundle\Event;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ladb\CoreBundle\Utils\TextureUtils;
use Ladb\CoreBundle\Utils\KnowledgeUtils;

class KnowledgeListener implements EventSubscriberInterface {

	const FIELD_VALUE_ADDED = 'ladb.knowledge.field_value_added';
	const FIELD_VALUE_UPDATED = 'ladb.knowledge.field_value_updated';
	const FIELD_VALUE_REMOVED = 'ladb.knowledge.field_value_removed';

	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public static function getSubscribedEvents() {
		return array(
			KnowledgeListener::FIELD_VALUE_ADDED   => 'onFieldValueAdded',
			KnowledgeListener::FIELD_VALUE_UPDATED => 'onFieldValueUpdated',
			KnowledgeListener::FIELD_VALUE_REMOVED => 'onFieldValueRemoved',
		);
	}

	/////

	public function onFieldValueAdded(KnowledgeEvent $event) {
		$knowledge = $event->getKnowledge();
		$field = $event->getData()['field'];

		// Update field
		$knowledgeUtils = $this->container->get(KnowledgeUtils::NAME);
		$knowledgeUtils->updateKnowledgeField($knowledge, $field);

		$knowledge->setUpdatedAt(new \DateTime());
		$knowledge->setChangedAt(new \DateTime());

		if ($knowledge instanceof \Ladb\CoreBundle\Entity\Knowledge\Wood
			&& ($field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_GRAIN || $field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_ENDGRAIN)) {

			$value = $event->getData()['value'];

			$textureUtils = $this->container->get(TextureUtils::NAME);
			$textureUtils->createTexture($knowledge, $value, false);

		}

	}

	public function onFieldValueUpdated(KnowledgeEvent $event) {
		$knowledge = $event->getKnowledge();
		$field = $event->getData()['field'];

		// Update field
		$knowledgeUtils = $this->container->get(KnowledgeUtils::NAME);
		$knowledgeUtils->updateKnowledgeField($knowledge, $field);

		$knowledge->setUpdatedAt(new \DateTime());

		if ($knowledge instanceof \Ladb\CoreBundle\Entity\Knowledge\Wood
			&& ($field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_GRAIN || $field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_ENDGRAIN)) {

			$value = $event->getData()['value'];

			$textureUtils = $this->container->get(TextureUtils::NAME);
			$textureUtils->updateTexture($knowledge, $value, false);

		}

	}

	public function onFieldValueRemoved(KnowledgeEvent $event) {
		$knowledge = $event->getKnowledge();
		$field = $event->getData()['field'];

		// Update field
		$knowledgeUtils = $this->container->get(KnowledgeUtils::NAME);
		$knowledgeUtils->updateKnowledgeField($knowledge, $field);

		$knowledge->setUpdatedAt(new \DateTime());
		$knowledge->setChangedAt(new \DateTime());

		if ($knowledge instanceof \Ladb\CoreBundle\Entity\Knowledge\Wood
			&& ($field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_GRAIN || $field == \Ladb\CoreBundle\Entity\Knowledge\Wood::FIELD_ENDGRAIN)) {

			$value = $event->getData()['value'];

			$textureUtils = $this->container->get(TextureUtils::NAME);
			$textureUtils->deleteTexture($knowledge, $value, false);

		}

	}

}