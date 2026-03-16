<?php

namespace App\Tests\Unit\Voter;

use App\Entity\Job;
use App\Entity\User;
use App\Security\Voter\JobVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class JobVoterTest extends TestCase
{
    private JobVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new JobVoter();
    }

    private function createUser(array $roles = []): User
    {
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setName('Test');
        $user->setPassword('hashed');
        $user->setRoles($roles);
        return $user;
    }

    private function createToken(?User $user = null): UsernamePasswordToken
    {
        $user = $user ?? $this->createUser();
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    public function testViewIsAlwaysGranted(): void
    {
        $job = new Job();
        $token = $this->createToken();

        $result = $this->voter->vote($token, $job, [JobVoter::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testCreateGrantedForEmployer(): void
    {
        $user = $this->createUser(['ROLE_EMPLOYER']);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, null, [JobVoter::CREATE]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testCreateDeniedForDeveloper(): void
    {
        $user = $this->createUser(['ROLE_DEVELOPER']);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, null, [JobVoter::CREATE]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
