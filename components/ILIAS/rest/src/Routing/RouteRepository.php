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

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteParser\Std;
use ILIAS\REST\Handlers\ActionResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use FastRoute\RouteCollector;
use RuntimeException;

class RouteRepository
{
    protected array $routes = [];
    protected array $routesByName = [];
    protected int $routeCounter = 0;
    protected ?string $cacheFile = null;
    protected string $basePath = '';

    public function __construct(
        protected ActionResolver           $actionResolver,
        protected ResponseFactoryInterface $responseFactory,
        ?string                  $cacheFile = null,
        protected ?RouteDispatcher $dispatcher = null
    )
    {
        
    }


    /**
     * Retrieve all registered routes.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Retrieve a route by its unique identifier.
     *
     * @param string $identifier
     *
     * @return Route
     */
    public function getRouteById(string $identifier): Route
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException("Route with identifier '{$identifier}' not found.");
        }
        return $this->routes[$identifier];
    }

    /**
     * Retrieve a route by its name.
     *
     * @param string $name
     *
     * @return Route
     */
    public function getRouteByName(string $name): Route
    {
        if (!isset($this->routesByName[$name])) {
            throw new RuntimeException("Route with name '{$name}' not found.");
        }
        return $this->routesByName[$name];
    }

    /**
     * Remove a route by its name.
     *
     * @param string $name
     */
    public function removeRouteByName(string $name): void
    {
        $route = $this->getRouteByName($name);
        unset($this->routes[$route->getIdentifier()], $this->routesByName[$name]);
    }

    /**
     * Add a new route with the specified methods, pattern, and handler.
     *
     * @param string[] $methods HTTP methods (e.g., GET, POST).
     * @param string $pattern The route pattern (e.g., "/home").
     * @param callable|string $handler The route handler.
     *
     * @return Route
     */
    public function map(array $methods, string $pattern, $handler): Route
    {
        return $this->create($methods, $pattern, $handler);
    }

    /**
     * Create a new route and register it in the repository.
     *
     * @param string[] $methods HTTP methods.
     * @param string $pattern Route pattern.
     * @param callable|string $handler Route handler.
     *
     * @return Route
     */
    protected function create(array $methods, string $pattern, $handler): Route
    {
        $route = new Route(
            $methods,
            $pattern,
            $handler,
            $this->responseFactory,
            $this->actionResolver,
            $this->routeCounter++
        );

        $this->routes[$route->getIdentifier()] = $route;

        /*$routeName = $route->getName();
        if ($routeName !== null) {
            if (isset($this->routesByName[$routeName])) {
                throw new RuntimeException("Route name '{$routeName}' is already in use.");
            }
            $this->routesByName[$routeName] = $route;
        }*/

        return $route;
    }


    /**
     * Lookup a route by its unique identifier.
     *
     * @param string $identifier The unique identifier of the route.
     *
     * @return Route
     */
    public function lookup(string $identifier): Route
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException("Route with identifier '{$identifier}' not found.");
        }

        return $this->routes[$identifier];
    }

    public function resolve(string $identifier): Route
    {
        return $this->lookup($identifier);
    }

    /**
     * Dispatch a route based on the HTTP method and URI.
     * Returns an array containing: 0-> status, 1: route identifier 2: routeArguments
     *
     * @param string $method HTTP method (e.g., GET).
     * @param string $uri URI (e.g., /home).
     *
     * @return array
     */
    public function dispatch(string $method, string $uri): array
    {
        $dispatcher = $this->getDispatcher();
        return $dispatcher->dispatch($method, $uri);
    }

    /**
     * Get allowed HTTP methods for a specific URI.
     *
     * @param string $uri
     *
     * @return string[]
     */
    public function getAllowedMethods(string $uri): array
    {
        $dispatcher = $this->getDispatcher();
        return $dispatcher->getAllowedMethods($uri);
    }

    /**
     * Retrieve or initialize the dispatcher.
     *
     * @return RouteDispatcher
     */
    protected function getDispatcher(): RouteDispatcher
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $this->dispatcher = $this->createDispatcher();
        return $this->dispatcher;
    }

    /**
     * Create a FastRoute dispatcher.
     *
     * @return \FastRoute\Dispatcher
     */
    protected function createDispatcher(): RouteDispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $collector): void {
            foreach ($this->routes as $route) {
                $collector->addRoute($route->getMethods(), $this->getBasePath() . $route->getPattern(), $route->getIdentifier());
            }
        };

        /** @var RouteDispatcher $dispatcher */
        $dispatcher =  \FastRoute\simpleDispatcher($routeDefinitionCallback, [
            'dataGenerator' => GroupCountBased::class,
            'dispatcher' => RouteDispatcher::class,
            'routeParser' => new Std(),
        ]);
        $this->dispatcher = $dispatcher;
        return $this->dispatcher;
    }
    /**
     * @return ActionResolver
     */public function getActionResolver(): ActionResolver
    {
        return $this->actionResolver;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     */
    public function withResponseFactory(ResponseFactoryInterface $responseFactory): self
    {
        $this->responseFactory = $responseFactory;
        return $this;
    }
}

