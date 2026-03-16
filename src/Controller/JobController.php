<?php

namespace App\Controller;

use App\DTO\Request\CreateJobRequest;
use App\DTO\Request\UpdateJobRequest;
use App\DTO\Response\JobResponse;
use App\Service\JobService;
use App\Service\RequestValidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/jobs')]
class JobController extends AbstractController
{
    public function __construct(
        private readonly JobService $jobService,
        private readonly RequestValidatorService $requestValidator,
    ) {}

    #[Route('', name: 'api_jobs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = $request->query->all();
        $jobs = $filters ? $this->jobService->findByFilters($filters) : $this->jobService->findOpenJobs();

        $response = array_map(fn($job) => JobResponse::fromEntity($job), $jobs);
        return $this->json($response);
    }

    #[Route('/{id}', name: 'api_jobs_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $job = $this->jobService->findById($id);
        $this->denyAccessUnlessGranted('JOB_VIEW', $job);

        return $this->json(JobResponse::fromEntity($job));
    }

    #[Route('', name: 'api_jobs_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Step 1: Authorization via Voter
        $this->denyAccessUnlessGranted('JOB_CREATE');

        // Step 2: Validate request body via DTO
        $dto = $this->requestValidator->validate($request, CreateJobRequest::class);

        // Step 3: Delegate to service
        $job = $this->jobService->create($dto, $this->getUser());

        // Step 4: Return response via DTO
        return $this->json(JobResponse::fromEntity($job), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_jobs_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $job = $this->jobService->findById($id);
        $this->denyAccessUnlessGranted('JOB_EDIT', $job);

        $dto = $this->requestValidator->validate($request, UpdateJobRequest::class);
        $job = $this->jobService->update($id, $dto);

        return $this->json(JobResponse::fromEntity($job));
    }

    #[Route('/{id}', name: 'api_jobs_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $job = $this->jobService->findById($id);
        $this->denyAccessUnlessGranted('JOB_DELETE', $job);

        $this->jobService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/employer/me', name: 'api_jobs_my', methods: ['GET'])]
    public function myJobs(): JsonResponse
    {
        $jobs = $this->jobService->findByEmployer($this->getUser());
        $response = array_map(fn($job) => JobResponse::fromEntity($job), $jobs);
        return $this->json($response);
    }
}
