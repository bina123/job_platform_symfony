<?php

namespace App\DTO\Response;

use App\Entity\User;

class UserResponse
{
    public int $id;
    public string $name;
    public string $email;
    public array $roles;
    public string $createdAt;

    public static function fromEntity(User $user): self
    {
        $dto = new self();
        $dto->id = $user->getId();
        $dto->name = $user->getName();
        $dto->email = $user->getEmail();
        $dto->roles = $user->getRoles();
        $dto->createdAt = $user->getCreatedAt()->format('c');
        return $dto;
    }
}
