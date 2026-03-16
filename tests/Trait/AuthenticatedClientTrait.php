<?php

namespace App\Tests\Trait;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthenticatedClientTrait
{
    private function createAuthenticatedClient(string $email = 'employer@test.com', string $role = 'employer'): KernelBrowser
    {
        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setName('Test ' . ucfirst($role));
            $user->setEmail($email);
            $user->setRoles(['ROLE_' . strtoupper($role)]);
            $user->setPassword($hasher->hashPassword($user, 'password123'));
            $em->persist($user);
            $em->flush();
        }

        // Get JWT token
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => 'password123',
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'] ?? '';

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);

        return $client;
    }

    private function getUser(KernelBrowser $client, string $email): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        return $em->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
