<?php

namespace App\EventListener;

use App\Event\JobCreatedEvent;
use App\Message\SendJobPostedNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: JobCreatedEvent::class)]
class JobCreatedListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function __invoke(JobCreatedEvent $event): void
    {
        $job = $event->getJob();
        $this->logger->info('New job created: {title} by {employer}', [
            'title' => $job->getTitle(),
            'employer' => $job->getEmployer()->getName(),
            'job_id' => $job->getId(),
        ]);

        $this->messageBus->dispatch(new SendJobPostedNotification($job->getId()));
    }
}
