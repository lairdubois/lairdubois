<?php

namespace App\Messenger;

use App\Entity\Core\User;
use App\Entity\Core\View;
use App\Model\AuthoredInterface;
use App\Utils\TypableUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ViewHandler implements MessageHandlerInterface {

    private EntityManagerInterface $om;
    private TypableUtils $typableUtils;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $om, TypableUtils $typableUtils, LoggerInterface $logger) {
        $this->om = $om;
        $this->typableUtils = $typableUtils;
        $this->logger = $logger;
    }

    public function __invoke(ViewMessage $message) {
        switch ($message->getKind()) {

            case View::KIND_LISTED:
                $this->_executeListedProcess($message->getEntityType(), $message->getEntityIds(), $message->getUserId());
                break;

            case View::KIND_SHOWN:
                if (count($message->getEntityIds()) > 0) {
                    foreach ($message->getEntityIds() as $entityId) {
                        $this->_executeShownProcess($message->getEntityType(), $entityId, $message->getUserId());
                    }
                }
                break;

            default:
                $this->logger->error('ViewHandler (Unknow kind='.$message->getKind().')');

        }
    }

    /////

    private function _executeListedProcess($entityType, $entityIds, $userId) {

        if (!is_null($userId)) {

            $user = $this->om->find(User::class, $userId);
            if (!is_null($user)) {

                $viewRepository = $this->om->getRepository(View::class);
                $viewedCount = $viewRepository->countByEntityTypeAndEntityIdsAndUserAndKind($entityType, $entityIds, $user, View::KIND_LISTED);
                if ($viewedCount < count($entityIds)) {

                    $newViewCount = 0;
                    foreach ($entityIds as $entityId) {

                        if (!$viewRepository->existsByEntityTypeAndEntityIdAndUserAndKind($entityType, $entityId, $user, View::KIND_LISTED)) {

                            // Create a new listed view
                            $view = new View();
                            $view->setEntityType($entityType);
                            $view->setEntityId($entityId);
                            $view->setUser($user);
                            $view->setKind(View::KIND_LISTED);

                            $this->om->persist($view);

                            $newViewCount++;
                        }

                    }

                    if ($newViewCount > 0) {
                        $this->om->flush();
                    }

                }

            }

        }

    }

    private function _executeShownProcess($entityType, $entityId, $userId) {

        // Retrieve viewable
        try {
            $viewable = $this->typableUtils->findTypable($entityType, $entityId);
        } catch(\Exception $e) {
            $this->logger->error('ViewHandler', array ( 'exception' => $e));
            return;
        }
        $updated = false;

        if (!is_null($userId)) {

            $userRepository = $this->om->getRepository(User::class);
            $user = $userRepository->findOneById($userId);
            if (!is_null($user)) {

                // Authenticated user -> use viewManager

                $viewRepository = $this->om->getRepository(View::class);
                $view = $viewRepository->findOneByEntityTypeAndEntityIdAndUserAndKind($viewable->getType(), $viewable->getId(), $user, View::KIND_SHOWN);
                if (is_null($view)) {

                    // Create a new view
                    $view = new View();
                    $view->setEntityType($viewable->getType());
                    $view->setEntityId($viewable->getId());
                    $view->setUser($user);
                    $view->setKind(View::KIND_SHOWN);

                    $this->om->persist($view);

                    // Exclude self contribution view and non public viewables
                    if ($viewable instanceof AuthoredInterface && $viewable->getUser()->getId() == $user->getId()) {
                        return;
                    }

                    // Increment viewCount
                    $viewable->incrementViewCount();

                    $updated = true;

                } else {

                    // Exclude self contribution view and non public viewables
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

            // Flush DB updates (view and/or entity)
            $this->om->flush();

            // Update in Elasticsearch

//			Elasticsearch update is temporarily removed due to a strange bug that remove item from index...

//			if ($viewable instanceof IndexableInterface && $viewable->isIndexable()) {
//				$this->searchUtils->replaceEntityInIndex($viewable);
//			}

        }

    }

}