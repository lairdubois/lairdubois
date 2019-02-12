<?php

namespace Ladb\CoreBundle\Utils;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Model\ViewableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ViewableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.viewable_utils';

	private $om;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		$this->om = $this->getDoctrine()->getManager();
	}

	/////

	public function deleteViews(ViewableInterface $viewable, $kind = View::KIND_NONE, $flush = true) {
		$viewRepository = $this->om->getRepository(View::CLASS_NAME);
		if ($kind == View::KIND_NONE) {
			$views = $viewRepository->findByEntityTypeAndEntityId($viewable->getType(), $viewable->getId());
		} else {
			$views = $viewRepository->findByEntityTypeAndEntityIdAndKind($viewable->getType(), $viewable->getId(), $kind);
		}
		foreach ($views as $view) {
			$this->om->remove($view);
		}
		if ($flush) {
			$this->om->flush();
		}
	}

	public function processShownView(ViewableInterface $viewable) {

		$CrawlerDetect = new CrawlerDetect();
		if ($CrawlerDetect->isCrawler()) {
			$this->container->get('logger')->error('Crawler detected and excluded from processShownView : '.$_SERVER['HTTP_USER_AGENT']);
			return;	// Exclude bots
		}

		$globalUtils = $this->container->get(GlobalUtils::NAME);
		$user = $globalUtils->getUser();
		if (is_null($user)) {

			// No user -> use sessions

			$session = $globalUtils->getSession();
			$key = '_ladb_viewable_'.$viewable->getType();
			$shownIds = $session->get($key);
			if (is_null($shownIds)) {
				$shownIds = array();
			}
			if (!in_array($viewable->getId(), $shownIds)) {
				$shownIds[] = $viewable->getId();
				$session->set($key, $shownIds);
			} else {
				return;
			}

		}

		try {

			// Publish a view in queue
			$producer = $this->container->get('old_sound_rabbit_mq.view_producer');
			$producer->publish(serialize(array(
				'kind'       => View::KIND_SHOWN,
				'entityType' => $viewable->getType(),
				'entityIds'  => array($viewable->getId()),
				'userId'     => !is_null($user) ? $user->getId() : null,
			)));

		} catch (\Exception $e) {
			$this->container->get('logger')->error('Failed to publish shown view process in queue : '.$e->getMessage());
		}

	}

	public function processListedView($viewables) {

		$globalUtils = $this->get(GlobalUtils::NAME);
		$user = $globalUtils->getUser();
		if (is_null($user)) {
			return;
		}

		$entityType = null;
		$entityIds = array();
		foreach($viewables as $viewable) {
			if ($viewable instanceof ViewableInterface) {
				$entityType = $viewable->getType();
				$entityIds[] = $viewable->getId();
			}
		}

		if (is_null($entityType)) {
			return;
		}

		try {

			// Publish a view in queue
			$producer = $this->container->get('old_sound_rabbit_mq.view_producer');
			$producer->publish(serialize(array(
				'kind'       => View::KIND_LISTED,
				'entityType' => $viewable->getType(),
				'entityIds'  => $entityIds,
				'userId'     => !is_null($user) ? $user->getId() : null,
			)));

		} catch (\Exception $e) {
			$this->container->get('logger')->error('Failed to publish shown view process in queue.');
		}

	}

	// Transfer /////

	public function transferViews(ViewableInterface $viewableSrc, ViewableInterface $viewableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$viewRepository = $this->om->getRepository(View::CLASS_NAME);

		// Retrieve views
		$views = $viewRepository->findByEntityTypeAndEntityId($viewableSrc->getType(), $viewableSrc->getId());

		// Transfer views
		foreach ($views as $view) {
			$view->setEntityType($viewableDest->getType());
			$view->setEntityId($viewableDest->getId());
		}

		// Update counters
		$viewableDest->incrementViewCount($viewableSrc->getViewCount());
		$viewableSrc->setViewCount(0);

		if ($flush) {
			$om->flush();
		}
	}

}