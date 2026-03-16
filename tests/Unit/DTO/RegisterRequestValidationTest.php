<?php

namespace App\Tests\Unit\DTO;

use App\DTO\Request\RegisterRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterRequestValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidRequest(): void
    {
        $dto = new RegisterRequest();
        $dto->name = 'John Doe';
        $dto->email = 'john@example.com';
        $dto->password = 'secret123';
        $dto->role = 'employer';

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testBlankNameFails(): void
    {
        $dto = new RegisterRequest();
        $dto->name = '';
        $dto->email = 'john@example.com';
        $dto->password = 'secret123';
        $dto->role = 'employer';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testInvalidEmailFails(): void
    {
        $dto = new RegisterRequest();
        $dto->name = 'John';
        $dto->email = 'not-an-email';
        $dto->password = 'secret123';
        $dto->role = 'employer';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testShortPasswordFails(): void
    {
        $dto = new RegisterRequest();
        $dto->name = 'John';
        $dto->email = 'john@example.com';
        $dto->password = '123';
        $dto->role = 'employer';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }

    public function testInvalidRoleFails(): void
    {
        $dto = new RegisterRequest();
        $dto->name = 'John';
        $dto->email = 'john@example.com';
        $dto->password = 'secret123';
        $dto->role = 'admin';

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, count($violations));
    }
}
