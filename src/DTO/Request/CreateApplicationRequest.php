<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateApplicationRequest
{
    #[Assert\Length(max: 5000, maxMessage: 'Cover letter must be at most {{ limit }} characters.')]
    public ?string $coverLetter = null;
}
