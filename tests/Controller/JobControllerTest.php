<?php

namespace App\Tests\Controller;

use App\Tests\Trait\AuthenticatedClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    public function testListJobsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/jobs');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateJobAsEmployer(): void
    {
        $client = $this->createAuthenticatedClient('employer-job@test.com', 'employer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Senior PHP Developer',
            'description' => 'We need a senior PHP developer',
            'salary' => 120000,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Senior PHP Developer', $data['title']);
        $this->assertEquals(120000, $data['salary']);
    }

    public function testCreateJobAsDeveloperFails(): void
    {
        $client = $this->createAuthenticatedClient('dev-nojob@test.com', 'developer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Some Job',
            'description' => 'Description',
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testShowJob(): void
    {
        $client = $this->createAuthenticatedClient('employer-show@test.com', 'employer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Show Job',
            'description' => 'Test description',
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $jobId = $data['id'];

        $client->request('GET', '/api/jobs/' . $jobId);
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Test Show Job', $data['title']);
    }

    public function testUpdateJob(): void
    {
        $client = $this->createAuthenticatedClient('employer-update@test.com', 'employer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Original Title',
            'description' => 'Original description',
        ]));
        $data = json_decode($client->getResponse()->getContent(), true);
        $jobId = $data['id'];

        $client->request('PUT', '/api/jobs/' . $jobId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated Title',
            'salary' => 150000,
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Title', $data['title']);
        $this->assertEquals(150000, $data['salary']);
    }

    public function testDeleteJob(): void
    {
        $client = $this->createAuthenticatedClient('employer-delete@test.com', 'employer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'To Delete',
            'description' => 'Will be deleted',
        ]));
        $data = json_decode($client->getResponse()->getContent(), true);
        $jobId = $data['id'];

        $client->request('DELETE', '/api/jobs/' . $jobId);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testCreateJobValidationFails(): void
    {
        $client = $this->createAuthenticatedClient('employer-valid@test.com', 'employer');

        $client->request('POST', '/api/jobs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => '',
            'description' => '',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }
}
