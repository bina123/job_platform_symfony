<?php

namespace App\Event;

use App\Entity\Application;
use Symfony\Contracts\EventDispatcher\Event;

class ApplicationStatusChangedEvent extends Event
{
    public function __construct(
        private readonly Application $application,
        private readonly string $oldStatus,
    ) {}

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }
}
