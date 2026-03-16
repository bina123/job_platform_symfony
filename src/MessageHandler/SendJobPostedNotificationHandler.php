<?php

namespace App\MessageHandler;

use App\Message\SendJobPostedNotification;
use App\Repository\JobRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendJobPostedNotificationHandler
{
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(SendJobPostedNotification $message): void
    {
        $job = $this->jobRepository->find($message->getJobId());
        if (!$job) {
            $this->logger->warning('Job {id} not found for notification', [
                'id' => $message->getJobId(),
            ]);
            return;
        }

        // In production, this would send emails to subscribed developers
        $this->logger->info('Sending job posted notification for {title} by {employer}', [
            'title' => $job->getTitle(),
            'employer' => $job->getEmployer()->getName(),
            'salary' => $job->getSalary(),
        ]);
    }
}
