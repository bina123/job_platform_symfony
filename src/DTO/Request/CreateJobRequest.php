<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateJobRequest
{
    #[Assert\NotBlank(message: 'The title field is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Title must be at most {{ limit }} characters.')]
    public string $title = '';

    #[Assert\NotBlank(message: 'The description field is required.')]
    public string $description = '';

    #[Assert\Positive(message: 'Salary must be a positive number.')]
    public ?int $salary = null;
}
