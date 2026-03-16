<?php

namespace App\Message;

class SendApplicationNotification
{
    public function __construct(private readonly int $applicationId) {}

    public function getApplicationId(): int
    {
        return $this->applicationId;
    }
}
