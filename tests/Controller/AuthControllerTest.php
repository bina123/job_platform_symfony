<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegisterSuccess(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'John Doe',
            'email' => 'john' . uniqid() . '@test.com',
            'password' => 'secret123',
            'role' => 'employer',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('John Doe', $data['name']);
        $this->assertContains('ROLE_EMPLOYER', $data['roles']);
    }

    public function testRegisterValidationFails(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
            'role' => 'invalid',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $email = 'login-test' . uniqid() . '@test.com';

        // Register first
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Login User',
            'email' => $email,
            'password' => 'secret123',
            'role' => 'developer',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Login
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => 'secret123',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nonexistent@test.com',
            'password' => 'wrong',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeEndpoint(): void
    {
        $client = static::createClient();
        $email = 'me-test' . uniqid() . '@test.com';

        // Register
        $client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Me User',
            'email' => $email,
            'password' => 'secret123',
            'role' => 'employer',
        ]));

        // Login
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => 'secret123',
        ]));
        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'];

        // Access /api/me
        $client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($email, $data['email']);
    }

    public function testMeWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }
}
