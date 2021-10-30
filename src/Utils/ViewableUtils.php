<?php

namespace App\Utils;

use App\Messenger\ViewMessage;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use App\Entity\Core\View;
use App\Model\ViewableInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ViewableUtils extends AbstractContainerAwareUtils {

	private $om;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		$this->om = $this->getDoctrine()->getManager();
	}

    public static function getSubscribedServices() {
	    return array_merge(parent::getSubscribedServices(), array(
            'logger' => '?'.LoggerInterface::class,
            '?'.MessageBusInterface::class,
        ));
    }

	/////

	public function deleteViews(ViewableInterface $viewable, $kind = View::KIND_NONE, $flush = true) {
		$viewRepository = $this->om->getRepository(View::class);
		if ($kind == View::KIND_NONE) {
			$viewRepository->deleteByEntityTypeAndEntityId($viewable->getType(), $viewable->getId());
		} else {
			$viewRepository->deleteByEntityTypeAndEntityIdAndKind($viewable->getType(), $viewable->getId(), $kind);
		}
		if ($flush) {
			$this->om->flush();
		}
	}

	public function processShownView(ViewableInterface $viewable) {

		$CrawlerDetect = new CrawlerDetect();
		if ($CrawlerDetect->isCrawler()) {
			$this->get('logger')->info('Crawler detected and excluded from processShownView : '.$_SERVER['HTTP_USER_AGENT']);
			return;	// Exclude bots
		}

		$globalUtils = $this->get(GlobalUtils::class);
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

		    $messageBus = $this->get(MessageBusInterface::class);
            $messageBus->dispatch(new ViewMessage(
                View::KIND_SHOWN,
				$viewable->getType(),
				array( $viewable->getId() ),
				!is_null($user) ? $user->getId() : null,
            ));

		} catch (\Exception $e) {
			$this->get('logger')->error('Failed to publish shown view process in queue', array ( 'exception' => $e));
		}

	}

	public function processListedView($viewables) {

		$globalUtils = $this->get(GlobalUtils::class);
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

            $messageBus = $this->get(MessageBusInterface::class);
            $messageBus->dispatch(new ViewMessage(
                View::KIND_LISTED,
                $entityType,
                $entityIds,
                !is_null($user) ? $user->getId() : null,
            ));

		} catch (\Exception $e) {
			$this->get('logger')->error('Failed to publish shown view process in queue.');
		}

	}

	// Transfer /////

	public function transferViews(ViewableInterface $viewableSrc, ViewableInterface $viewableDest, $flush = true) {
		$om = $this->getDoctrine()->getManager();
		$viewRepository = $this->om->getRepository(View::class);

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