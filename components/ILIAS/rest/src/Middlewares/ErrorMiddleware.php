<?php

declare(strict_types=1);

namespace ILIAS\REST\Middleware;

use ilLogger;
use ilLoggerFactory;
use ILIAS\REST\Handlers\ActionResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
    private ?ilLogger $logger = null;
    public function __construct(
        protected ActionResolver $actionResolver,
        protected ResponseFactoryInterface $responseFactory,
        protected bool $displayErrorDetails = false,
        protected bool $logErrors = false,
        protected bool $logErrorDetails = false
    ) {

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($request, $exception);
        }
    }

    private function createErrorResponse(int $statusCode, string $message, ?array $details = null): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $error = ['status' => $statusCode, 'message' => $message];
        if ($details) {
            $error['details'] = $details;
        }
        $response->getBody()->write(json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }

    protected function handleException(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        if ($this->logErrors) {
            $this->logException($exception);
        }

        $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 600 ? $exception->getCode() : 500;
        $message = $exception->getMessage() ?: 'An unexpected error occurred.';
        $details = $this->displayErrorDetails ? $this->getErrorDetails($exception) : null;

        return $this->createErrorResponse($statusCode, $message, $details);
    }

    protected function logException(Throwable $exception): void
    {
        $context = $this->logErrorDetails ? [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ] : [];
        $this->getLogger()->error($exception->getMessage(), $context);
        $this->getLogger()->error($exception->getTraceAsString(), $context);
    }


    protected function getErrorDetails(Throwable $exception): array
    {
        return [
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }

    private function getLogger(): ilLogger
    {
        if ($this->logger === null) {
            $this->logger = ilLoggerFactory::getLogger('rest');
        }
        return $this->logger;
    }
}