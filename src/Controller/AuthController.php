<?php

namespace App\Controller;

use App\DTO\Request\RegisterRequest;
use App\DTO\Response\UserResponse;
use App\Service\RequestValidatorService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RequestValidatorService $requestValidator,
    ) {}

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->requestValidator->validate($request, RegisterRequest::class);
        $user = $this->userService->register($dto);

        return $this->json(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This is handled by the json_login authenticator
        // If we reach here, authentication failed
        return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json(UserResponse::fromEntity($user));
    }
}
