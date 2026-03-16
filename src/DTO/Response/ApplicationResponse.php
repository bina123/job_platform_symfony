<?php

namespace App\DTO\Response;

use App\Entity\Application;

class ApplicationResponse
{
    public int $id;
    public string $status;
    public ?string $coverLetter;
    public string $createdAt;
    public array $job;
    public array $developer;

    public static function fromEntity(Application $application): self
    {
        $dto = new self();
        $dto->id = $application->getId();
        $dto->status = $application->getStatus();
        $dto->coverLetter = $application->getCoverLetter();
        $dto->createdAt = $application->getCreatedAt()->format('c');
        $dto->job = [
            'id' => $application->getJob()->getId(),
            'title' => $application->getJob()->getTitle(),
        ];
        $dto->developer = [
            'id' => $application->getDeveloper()->getId(),
            'name' => $application->getDeveloper()->getName(),
        ];
        return $dto;
    }
}
