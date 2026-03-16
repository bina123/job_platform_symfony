<?php

namespace App\Service;

use App\DTO\Request\CreateApplicationRequest;
use App\Entity\Application;
use App\Entity\Job;
use App\Entity\User;
use App\Event\ApplicationStatusChangedEvent;
use App\Event\ApplicationSubmittedEvent;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ApplicationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ApplicationRepository $applicationRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function apply(Job $job, User $developer, CreateApplicationRequest $dto): Application
    {
        if ($job->getStatus() !== Job::STATUS_OPEN) {
            throw new ValidationException(['job' => 'This job is no longer accepting applications']);
        }

        if ($this->applicationRepository->hasUserApplied($developer, $job)) {
            throw new ValidationException(['job' => 'You have already applied to this job']);
        }

        $application = new Application();
        $application->setJob($job);
        $application->setDeveloper($developer);
        $application->setCoverLetter($dto->coverLetter);

        $this->em->persist($application);
        $this->em->flush();

        $this->dispatcher->dispatch(new ApplicationSubmittedEvent($application));

        return $application;
    }

    public function updateStatus(int $id, string $status): Application
    {
        $application = $this->findById($id);
        $oldStatus = $application->getStatus();
        $application->setStatus($status);

        $this->em->flush();

        $this->dispatcher->dispatch(new ApplicationStatusChangedEvent($application, $oldStatus));

        return $application;
    }

    public function findById(int $id): Application
    {
        $application = $this->applicationRepository->find($id);
        if (!$application) {
            throw new ResourceNotFoundException('Application', $id);
        }
        return $application;
    }

    /** @return Application[] */
    public function findByDeveloper(User $developer): array
    {
        return $this->applicationRepository->findByDeveloper($developer);
    }

    /** @return Application[] */
    public function findByJob(Job $job): array
    {
        return $this->applicationRepository->findByJob($job);
    }
}
