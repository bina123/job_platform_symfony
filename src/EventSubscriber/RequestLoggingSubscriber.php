<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestLoggingSubscriber implements EventSubscriberInterface
{
    private float $startTime = 0;

    public function __construct(private readonly LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 200],
            KernelEvents::RESPONSE => ['onResponse', -200],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->startTime = microtime(true);
        $request = $event->getRequest();

        $this->logger->info('API Request: {method} {path}', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $duration = round((microtime(true) - $this->startTime) * 1000, 2);
        $response = $event->getResponse();
        $request = $event->getRequest();

        $this->logger->info('API Response: {method} {path} {status} ({duration}ms)', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'duration' => $duration,
        ]);
    }
}
