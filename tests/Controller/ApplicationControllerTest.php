<?php

namespace App\Tests\Controller;

use App\Entity\Job;
use App\Entity\User;
use App\Tests\Trait\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApplicationControllerTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    private function createJobInDb(): int
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $employer = $em->getRepository(User::class)->findOneBy(['email' => 'employer-app@test.com']);
        if (!$employer) {
            $employer = new User();
            $employer->setName('Test Employer');
            $employer->setEmail('employer-app@test.com');
            $employer->setRoles(['ROLE_EMPLOYER']);
            $employer->setPassword($hasher->hashPassword($employer, 'password123'));
            $em->persist($employer);
        }

        $job = new Job();
        $job->setTitle('Job for Applications ' . uniqid());
        $job->setDescription('Test job for application tests');
        $job->setSalary(100000);
        $job->setEmployer($employer);
        $em->persist($job);
        $em->flush();

        return $job->getId();
    }

    public function testApplyToJob(): void
    {
        $client = $this->createAuthenticatedClient('dev-apply@test.com', 'developer');
        $jobId = $this->createJobInDb();

        $client->request('POST', '/api/jobs/' . $jobId . '/apply', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'coverLetter' => 'I am a great fit for this role!',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals('I am a great fit for this role!', $data['coverLetter']);
    }

    public function testEmployerCannotApply(): void
    {
        $client = $this->createAuthenticatedClient('employer-noapply@test.com', 'employer');
        $jobId = $this->createJobInDb();

        $client->request('POST', '/api/jobs/' . $jobId . '/apply', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'coverLetter' => 'I want to apply',
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListMyApplications(): void
    {
        $client = $this->createAuthenticatedClient('dev-list@test.com', 'developer');

        $client->request('GET', '/api/applications');
        $this->assertResponseIsSuccessful();
    }
}
