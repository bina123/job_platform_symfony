<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'The name field is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Name must be at most {{ limit }} characters.')]
    public string $name = '';

    #[Assert\NotBlank(message: 'The email field is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'The password field is required.')]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: 'Password must be at least {{ limit }} characters.',
        maxMessage: 'Password must be at most {{ limit }} characters.',
    )]
    public string $password = '';

    #[Assert\NotBlank(message: 'The role field is required.')]
    #[Assert\Choice(
        choices: ['employer', 'developer'],
        message: 'Role must be either "employer" or "developer".',
    )]
    public string $role = '';
}
