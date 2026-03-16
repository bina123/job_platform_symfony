<?php

namespace App\DTO\Response;

use App\Entity\Job;

class JobResponse
{
    public int $id;
    public string $title;
    public ?string $description;
    public ?int $salary;
    public string $status;
    public string $createdAt;
    public array $employer;
    public int $applicationCount;

    public static function fromEntity(Job $job): self
    {
        $dto = new self();
        $dto->id = $job->getId();
        $dto->title = $job->getTitle();
        $dto->description = $job->getDescription();
        $dto->salary = $job->getSalary();
        $dto->status = $job->getStatus();
        $dto->createdAt = $job->getCreatedAt()->format('c');
        $dto->employer = [
            'id' => $job->getEmployer()->getId(),
            'name' => $job->getEmployer()->getName(),
        ];
        $dto->applicationCount = $job->getApplications()->count();
        return $dto;
    }
}
