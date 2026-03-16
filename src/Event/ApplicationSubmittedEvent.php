<?php

namespace App\Event;

use App\Entity\Application;
use Symfony\Contracts\EventDispatcher\Event;

class ApplicationSubmittedEvent extends Event
{
    public function __construct(private readonly Application $application) {}

    public function getApplication(): Application
    {
        return $this->application;
    }
}
