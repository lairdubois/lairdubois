<?php

namespace App\Messenger;

class ViewMessage {

    private int $kind;
    private int $entityType;
    private array $entityIds;
    private ?int $userId;

    public function __construct(int $kind, int $entityType, array $entityIds, ?int $userId = null) {
        $this->kind = $kind;
        $this->entityType = $entityType;
        $this->entityIds = $entityIds;
        $this->userId = $userId;
    }

    public function getKind(): int {
        return $this->kind;
    }

    public function getEntityType(): int {
        return $this->entityType;
    }

    public function getEntityIds(): array {
        return $this->entityIds;
    }

    public function getUserId(): int {
        return $this->userId;
    }

}