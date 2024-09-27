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
use FastRoute\RouteParser\Std;
use ILIAS\REST\Routing\RouteRepository;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


class RoutingMiddleware implements MiddlewareInterface
{
    protected RouteRepository $routeRepository;
    protected Std $routeParser;

    public function __construct(RouteRepository $routeRepository)
    {
        $this->routeRepository = $routeRepository;
        $this->routeParser = new Std();
    }

    /**
     * Process the incoming request and attach routing details.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->performRouting($request);
        return $handler->handle($request);
    }

    /**
     * Perform routing and attach resolved route and arguments to the request.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        // Attach route parser methods
        $request = $request->withAttribute('__parse__', [$this, 'generateUri']);

        // Dispatch the request using the route repository
        [$status, $routeIdentifier, $routeArguments] = $this->routeRepository->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        // Attach routing results to the request
        $request = $request->withAttribute('__details__', compact('status', 'routeIdentifier', 'routeArguments'));

        // Handle routing results
        switch ($status) {
            case \FastRoute\Dispatcher::FOUND:
                $route = $this->routeRepository->resolve($routeIdentifier)
                    ->withArguments($routeArguments);
                return $request->withAttribute('__route__', $route);

            case \FastRoute\Dispatcher::NOT_FOUND:
                throw new RuntimeException('Route not found for the requested URI.');

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new RuntimeException('HTTP method not allowed for the requested URI.');

            default:
                throw new RuntimeException('An unexpected error occurred during routing.');
        }
    }

    /**
     * Generate a URI for a named route with optional parameters.
     *
     * @param string $routeName
     * @param array $parameters
     * @param array $queryParameters
     *
     * @return string
     * @throws RuntimeException
     */
    public function generateUri(string $routeName, array $parameters = [], array $queryParameters = []): string
    {
        $route = $this->routeRepository->getRouteByName($routeName);
        $pattern = $route->getPattern();
        $routeVariants = $this->routeParser->parse($pattern);

        foreach ($routeVariants as $routeDefinition) {
            $pathComponents = [];
            $missing = false;

            foreach ($routeDefinition as $component) {
                if (is_string($component)) {
                    $pathComponents[] = $component;
                } elseif (isset($parameters[$component[0]])) {
                    $pathComponents[] = $parameters[$component[0]];
                } else {
                    $missing = true;
                    break;
                }
            }

            if (!$missing) {
                $uri = implode('', $pathComponents);

                if (!empty($queryParameters)) {
                    $uri .= '?' . http_build_query($queryParameters);
                }

                return $uri;
            }
        }

        throw new RuntimeException('Unable to generate URI: missing parameters for route.');
    }
}