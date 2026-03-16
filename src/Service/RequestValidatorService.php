<?php

namespace App\Service;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidatorService
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    public function validate(Request $request, string $dtoClass): object
    {
        $content = $request->getContent();
        if (empty($content)) {
            throw new ValidationException(['body' => 'Request body is empty']);
        }

        try {
            // 1. Deserialize JSON → DTO object
            $dto = $this->serializer->deserialize($content, $dtoClass, 'json');
        } catch (\Throwable $e) {
            throw new ValidationException(['body' => 'Invalid JSON: ' . $e->getMessage()]);
        }

        // 2. Run validation constraints on the DTO
        $violations = $this->validator->validate($dto);

        // 3. If violations exist, throw structured error
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationException($errors); // → 422 response
        }

        return $dto;
    }
}
