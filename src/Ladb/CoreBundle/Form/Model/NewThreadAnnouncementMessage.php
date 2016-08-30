<?php

namespace Ladb\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class NewThreadAnnouncementMessage {

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=255)
     */
    private $subject;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=10000)
     */
    private $body;

    // Subject /////

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getSubject() {
        return $this->subject;
    }

    // Body /////

    public function setBody($body) {
        $this->body = $body;
        return $this;
    }

    public function getBody() {
        return $this->body;
    }

}