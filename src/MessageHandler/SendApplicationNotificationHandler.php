<?php

namespace App\MessageHandler;

use App\Message\SendApplicationNotification;
use App\Repository\ApplicationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendApplicationNotificationHandler
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(SendApplicationNotification $message): void
    {
        $application = $this->applicationRepository->find($message->getApplicationId());
        if (!$application) {
            $this->logger->warning('Application {id} not found for notification', [
                'id' => $message->getApplicationId(),
            ]);
            return;
        }

        $employer = $application->getJob()->getEmployer();
        $developer = $application->getDeveloper();

        // In production, this would send an email via Mailer
        $this->logger->info('Sending application notification email to {email} about application from {developer} for {job}', [
            'email' => $employer->getEmail(),
            'developer' => $developer->getName(),
            'job' => $application->getJob()->getTitle(),
            'status' => $application->getStatus(),
        ]);
    }
}
