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

declare(strict_types=1);

namespace ILIAS\REST\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ILIAS\REST\Middleware\RoutingMiddleware;
use RuntimeException;

class StackTipHandler implements RequestHandlerInterface
{
    private RouteRepository $routes;

    public function __construct(RouteRepository $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Handles the request by performing routing and dispatching the appropriate route.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RuntimeException If no matching route is found or routing fails.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Perform routing if not already done
        if ($request->getAttribute('__details__') === null) {
            $routingMiddleware = new RoutingMiddleware($this->routes);
            $request = $routingMiddleware->performRouting($request);
        }

        // Retrieve the matched route
        /**
         * @var Route $route
         */
        $route = $request->getAttribute('__route__');
        if ($route === null) {
            throw new RuntimeException('No matching route found.');
        }

        // Dispatch the route and return its response
        return $route->run($request);
    }
}