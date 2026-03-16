<?php

namespace App\Service;

use App\DTO\Request\RegisterRequest;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function register(RegisterRequest $dto): User
    {
        if ($this->userRepository->findByEmail($dto->email)) {
            throw new ValidationException(['email' => 'This email is already registered']);
        }

        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setRoles(['ROLE_' . strtoupper($dto->role)]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function findById(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new ResourceNotFoundException('User', $id);
        }
        return $user;
    }
}
