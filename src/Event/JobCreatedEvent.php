<?php

namespace App\Event;

use App\Entity\Job;
use Symfony\Contracts\EventDispatcher\Event;

class JobCreatedEvent extends Event
{
    public function __construct(private readonly Job $job) {}

    public function getJob(): Job
    {
        return $this->job;
    }
}
