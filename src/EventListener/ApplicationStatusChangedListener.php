<?php

namespace App\EventListener;

use App\Event\ApplicationStatusChangedEvent;
use App\Message\SendApplicationNotification;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: ApplicationStatusChangedEvent::class)]
class ApplicationStatusChangedListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function __invoke(ApplicationStatusChangedEvent $event): void
    {
        $application = $event->getApplication();
        $this->logger->info('Application status changed from {old} to {new} for application {id}', [
            'old' => $event->getOldStatus(),
            'new' => $application->getStatus(),
            'id' => $application->getId(),
        ]);

        $this->messageBus->dispatch(new SendApplicationNotification($application->getId()));
    }
}
