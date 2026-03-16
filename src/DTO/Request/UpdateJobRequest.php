<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateJobRequest
{
    #[Assert\Length(max: 255, maxMessage: 'Title must be at most {{ limit }} characters.')]
    public ?string $title = null;

    public ?string $description = null;

    #[Assert\Positive(message: 'Salary must be a positive number.')]
    public ?int $salary = null;

    #[Assert\Choice(choices: ['open', 'closed'], message: 'Status must be either "open" or "closed".')]
    public ?string $status = null;
}
