<?php

namespace App\Messenger;

class WebpushNotificationMessage {

    private int $userId;
    private string $body;
    private string $icon;
    private string $link;

    public function __construct(int $userId, string $body, string $icon, string $link) {
        $this->userId = $userId;
        $this->body = $body;
        $this->icon = $icon;
        $this->link = $link;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getIcon(): string {
        return $this->icon;
    }

    public function getLink(): string {
        return $this->link;
    }

}