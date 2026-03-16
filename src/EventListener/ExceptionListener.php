<?php

namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as SecurityAccessDeniedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($exception instanceof ApiException) {
            $data = [
                'error' => $exception->getMessage(),
                'code' => $exception->getStatusCode(),
            ];
            if ($exception->getErrors()) {
                $data['errors'] = $exception->getErrors();
            }
            $event->setResponse(new JsonResponse($data, $exception->getStatusCode()));
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Resource not found',
                'code' => 404,
            ], 404));
            return;
        }

        if ($exception instanceof AccessDeniedHttpException || $exception instanceof SecurityAccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Access denied',
                'code' => 403,
            ], 403));
            return;
        }

        $data = [
            'error' => 'Internal server error',
            'code' => 500,
        ];
        if ($_SERVER['APP_ENV'] === 'test' || $_SERVER['APP_ENV'] === 'dev') {
            $data['debug'] = $exception->getMessage();
            $data['trace'] = $exception->getTraceAsString();
        }
        $event->setResponse(new JsonResponse($data, 500));
    }
}
