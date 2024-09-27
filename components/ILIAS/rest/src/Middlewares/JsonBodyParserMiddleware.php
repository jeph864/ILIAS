<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\REST\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RuntimeException;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
    /**
     * Process the incoming request, parse the JSON body, and pass it to the next handler.
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($this->isJsonRequest($request)) {
            $parsedBody = $this->parseJsonBody($request);
            $request = $request->withParsedBody($parsedBody);
        }

        return $handler->handle($request);
    }

    /**
     * Check if the request contains a JSON body.
     */
    private function isJsonRequest(Request $request): bool
    {
        return str_contains($request->getHeaderLine('Content-Type'), 'application/json');
    }

    /**
     * Parse the JSON body of the request.
     *
     * @throws RuntimeException if the JSON body is invalid.
     */
    private function parseJsonBody(Request $request): array
    {
        $body = (string) $request->getBody();
        $parsed = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON body: ' . json_last_error_msg());
        }

        return $parsed ?? [];
    }
}
