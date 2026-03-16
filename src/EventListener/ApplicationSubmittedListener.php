<?php

namespace App\EventListener;

use App\Event\ApplicationSubmittedEvent;
use App\Message\SendApplicationNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: ApplicationSubmittedEvent::class)]
class ApplicationSubmittedListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function __invoke(ApplicationSubmittedEvent $event): void
    {
        $application = $event->getApplication();
        $this->logger->info('New application submitted by {developer} for job {job}', [
            'developer' => $application->getDeveloper()->getName(),
            'job' => $application->getJob()->getTitle(),
            'application_id' => $application->getId(),
        ]);

        $this->messageBus->dispatch(new SendApplicationNotification($application->getId()));
    }
}
