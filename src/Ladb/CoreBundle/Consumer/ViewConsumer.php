<?php
namespace Ladb\CoreBundle\Consumer;

use Ladb\CoreBundle\Entity\Core\User;
use Ladb\CoreBundle\Entity\Core\View;
use Ladb\CoreBundle\Model\AuthoredInterface;
use Ladb\CoreBundle\Model\IndexableInterface;
use Ladb\CoreBundle\Utils\SearchUtils;
use Ladb\CoreBundle\Utils\TypableUtils;
use OldSound\RabbitMqBundle\RabbitMq\BatchConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ViewConsumer implements ConsumerInterface {

	protected $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/////

	public function execute(AMQPMessage $msg) {
		$om = $this->container->get('doctrine')->getManager();
		$userRepository = $om->getRepository(User::CLASS_NAME);
		$viewRepository = $om->getRepository(View::CLASS_NAME);
		$typableUtils = $this->container->get(TypableUtils::NAME);
		$logger = $this->container->get('logger');

		try {
			$msgBody = unserialize($msg->getBody());

			$entityType = $msgBody['entityType'];
			$entityId = $msgBody['entityId'];
			$userId = $msgBody['userId'];

		} catch (\Exception $e) {
			$logger->error($e);
			return;
		}

		// Retrieve viewable
		try {
			$viewable = $typableUtils->findTypable($entityType, $entityId);
		} catch(\Exception $e) {
			$logger->error($e);
			return;
		}
		$updated = false;

		if (!is_null($userId)) {

			$user = $userRepository->findOneById($userId);
			if (!is_null($user)) {

				// Authenticated user -> use viewManager

				$view = $viewRepository->findOneByEntityTypeAndEntityIdAndUserAndKind($viewable->getType(), $viewable->getId(), $user, View::KIND_SHOWN);
				if (is_null($view)) {

					// Create a new view
					$view = new View();
					$view->setEntityType($viewable->getType());
					$view->setEntityId($viewable->getId());
					$view->setUser($user);
					$view->setKind(View::KIND_SHOWN);

					$om->persist($view);

					// Exclude self contribution view
					if ($viewable instanceof AuthoredInterface && $viewable->getUser()->getId() == $user->getId()) {
						return;
					}

					// Increment viewCount
					$viewable->incrementViewCount();

					$updated = true;

				} else {

					// Exclude self contribution view
					if ($viewable instanceof AuthoredInterface && $viewable->getUser()->getId() == $user->getId()) {
						return;
					}

					if ($view->getCreatedAt() <= (new \DateTime())->sub(new \DateInterval('P1D'))) { // 1 day

						// View is older than 1 day. Update view, increment view count.

						// Reset view createAt
						$view->setCreatedAt(new \DateTime());

						// Increment viewCount
						$viewable->incrementViewCount();

						$updated = true;

					}

				}

			}

		} else {

			// Increment viewCount
			$viewable->incrementViewCount();

			$updated = true;

		}

		if ($updated) {

			// Update in Elasticsearch
			if ($viewable instanceof IndexableInterface && $viewable->isIndexable()) {
				$searchUtils = $this->container->get(SearchUtils::NAME);
				$searchUtils->replaceEntityInIndex($viewable);
			}

			// Flush DB updates (view and/or entity)
			$om->flush();

		}

	}

}