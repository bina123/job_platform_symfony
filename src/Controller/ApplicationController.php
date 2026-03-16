<?php

namespace App\Controller;

use App\DTO\Request\CreateApplicationRequest;
use App\DTO\Response\ApplicationResponse;
use App\Service\ApplicationService;
use App\Service\JobService;
use App\Service\RequestValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly JobService $jobService,
        private readonly RequestValidatorService $requestValidator,
    ) {}

    #[Route('/api/jobs/{id}/apply', name: 'api_jobs_apply', methods: ['POST'])]
    public function apply(int $id, Request $request): JsonResponse
    {
        $job = $this->jobService->findById($id);
        $this->denyAccessUnlessGranted('APPLICATION_CREATE', $job);

        $dto = $this->requestValidator->validate($request, CreateApplicationRequest::class);
        $application = $this->applicationService->apply($job, $this->getUser(), $dto);

        return $this->json(ApplicationResponse::fromEntity($application), Response::HTTP_CREATED);
    }

    #[Route('/api/applications', name: 'api_applications_list', methods: ['GET'])]
    public function myApplications(): JsonResponse
    {
        $applications = $this->applicationService->findByDeveloper($this->getUser());
        $response = array_map(fn($a) => ApplicationResponse::fromEntity($a), $applications);
        return $this->json($response);
    }

    #[Route('/api/applications/{id}', name: 'api_applications_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $application = $this->applicationService->findById($id);
        $this->denyAccessUnlessGranted('APPLICATION_VIEW', $application);

        return $this->json(ApplicationResponse::fromEntity($application));
    }

    #[Route('/api/applications/{id}/status', name: 'api_applications_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $application = $this->applicationService->findById($id);
        $this->denyAccessUnlessGranted('APPLICATION_MANAGE', $application);

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!in_array($status, ['accepted', 'rejected'], true)) {
            return $this->json(['error' => 'Invalid status. Must be "accepted" or "rejected"'], 422);
        }

        $application = $this->applicationService->updateStatus($id, $status);
        return $this->json(ApplicationResponse::fromEntity($application));
    }

    #[Route('/api/jobs/{id}/applications', name: 'api_jobs_applications', methods: ['GET'])]
    public function jobApplications(int $id): JsonResponse
    {
        $job = $this->jobService->findById($id);
        $this->denyAccessUnlessGranted('JOB_EDIT', $job);

        $applications = $this->applicationService->findByJob($job);
        $response = array_map(fn($a) => ApplicationResponse::fromEntity($a), $applications);
        return $this->json($response);
    }
}
