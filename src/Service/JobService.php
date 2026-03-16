<?php

namespace App\Service;

use App\DTO\Request\CreateJobRequest;
use App\DTO\Request\UpdateJobRequest;
use App\Entity\Job;
use App\Entity\User;
use App\Event\JobCreatedEvent;
use App\Exception\ResourceNotFoundException;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class JobService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JobRepository $jobRepository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function create(CreateJobRequest $dto, User $employer): Job
    {
        $job = new Job();
        $job->setTitle($dto->title);
        $job->setDescription($dto->description);
        $job->setSalary($dto->salary);
        $job->setEmployer($employer);

        $this->em->persist($job);
        $this->em->flush();

        $this->dispatcher->dispatch(new JobCreatedEvent($job));

        return $job;
    }

    public function update(int $id, UpdateJobRequest $dto): Job
    {
        $job = $this->findById($id);

        if ($dto->title !== null) {
            $job->setTitle($dto->title);
        }
        if ($dto->description !== null) {
            $job->setDescription($dto->description);
        }
        if ($dto->salary !== null) {
            $job->setSalary($dto->salary);
        }
        if ($dto->status !== null) {
            $job->setStatus($dto->status);
        }

        $this->em->flush();

        return $job;
    }

    public function delete(int $id): void
    {
        $job = $this->findById($id);
        $this->em->remove($job);
        $this->em->flush();
    }

    public function findById(int $id): Job
    {
        $job = $this->jobRepository->find($id);
        if (!$job) {
            throw new ResourceNotFoundException('Job', $id);
        }
        return $job;
    }

    /** @return Job[] */
    public function findAll(): array
    {
        return $this->jobRepository->findAll();
    }

    /** @return Job[] */
    public function findByEmployer(User $employer): array
    {
        return $this->jobRepository->findByEmployer($employer);
    }

    /** @return Job[] */
    public function findOpenJobs(): array
    {
        return $this->jobRepository->findOpenJobs();
    }

    /** @return Job[] */
    public function findByFilters(array $filters): array
    {
        return $this->jobRepository->findByFilters($filters);
    }
}
