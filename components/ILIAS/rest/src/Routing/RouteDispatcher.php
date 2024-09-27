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

use FastRoute\Dispatcher\GroupCountBased;


class RouteDispatcher extends GroupCountBased
{
    /**
     * Cache of allowed HTTP methods for specific URIs
     *
     * @var string[][]
     */
    private array $allowedMethods = [];

    /**
     * Dispatch the route based on HTTP method and URI
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array{int, string|null, array<string, string>}
     */
    public function dispatch($httpMethod, $uri): array
    {
        $routingResults = $this->evaluateRoutingResults($httpMethod, $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        // Attempt fallback to GET for HEAD requests
        if ($httpMethod === 'HEAD') {
            $routingResults = $this->evaluateRoutingResults('GET', $uri);
            if ($routingResults[0] === self::FOUND) {
                return $routingResults;
            }
        }

        // Attempt fallback to wildcard routes
        $routingResults = $this->evaluateRoutingResults('*', $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        // Check if the URI has other allowed methods
        if (!empty($this->getAllowedMethods($uri))) {
            return [self::METHOD_NOT_ALLOWED, null, []];
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * Evaluate the routing results for a specific HTTP method and URI
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array{int, string|null, array<string, string>}
     */
    private function evaluateRoutingResults(string $httpMethod, string $uri): array
    {
        // Check static routes
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            /** @var string $routeIdentifier */
            $routeIdentifier = $this->staticRouteMap[$httpMethod][$uri];
            return [self::FOUND, $routeIdentifier, []];
        }

        // Check variable routes
        if (isset($this->variableRouteData[$httpMethod])) {
            /** @var array{0: int, 1?: string, 2?: array<string, string>} $result */
            $result = $this->dispatchVariableRoute($this->variableRouteData[$httpMethod], $uri);
            if ($result[0] === self::FOUND) {
                /** @var array{int, string, array<string, string>} $result */
                return [self::FOUND, $result[1], $result[2]];
            }
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * Retrieve allowed HTTP methods for a specific URI
     *
     * @param string $uri
     *
     * @return string[]
     */
    public function getAllowedMethods(string $uri): array
    {
        if (isset($this->allowedMethods[$uri])) {
            return $this->allowedMethods[$uri];
        }

        $allowedMethods = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if (isset($uriMap[$uri])) {
                $allowedMethods[$method] = true;
            }
        }

        foreach ($this->variableRouteData as $method => $routeData) {
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $allowedMethods[$method] = true;
            }
        }

        return $this->allowedMethods[$uri] = array_keys($allowedMethods);
    }

}