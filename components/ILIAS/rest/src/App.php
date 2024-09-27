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
namespace ILIAS\REST;

use ILIAS\HTTP\RawHTTPServices;
use ILIAS\HTTP\Services;
use ILIAS\REST\Handlers\ActionResolver;
use ILIAS\REST\Middleware\JsonBodyParserMiddleware;
use ILIAS\REST\Middleware\MiddlewareStack;
use ILIAS\REST\Routing\Route;
use ILIAS\REST\Routing\RouteRepository;
use ILIAS\REST\Middleware\RoutingMiddleware;
use ILIAS\REST\Routing\StackTipHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ILIAS\REST\Middleware\ErrorMiddleware;

class App
{
    private ActionResolver $actionResolver;
    protected string $basePath;
    private Services $http;
    public function __construct(
        protected RouteRepository $routeRepository,
        protected ?MiddlewareStack $middlewareStack = null,
        protected  ?ContainerInterface $container = null,

        protected int  $chunkSize = 4096
    ) {
        global $DIC;
        $this->http = $DIC->http();
        $this->actionResolver = $this->routeRepository->getActionResolver();
        if (!$this->middlewareStack){
            $this->middlewareStack = new MiddlewareStack(
                new StackTipHandler($this->routeRepository),
                $this->actionResolver,
                $this->container

            );
        }
        $this->registerDefaultMiddleware();
    }

    /**
     * Register default middleware.
     */
    protected function registerDefaultMiddleware(): void
    {
        $this->addMiddleware(new RoutingMiddleware($this->routeRepository));
        $this->addMiddleware(new JsonBodyParserMiddleware());
        $this->addMiddleware(new ErrorMiddleware(
            $this->actionResolver,
            $this->routeRepository->getResponseFactory(),
            false,
            true,
            true
        ));
    }

    /**
     * Add middleware to the stack.
     *
     * @param callable|MiddlewareInterface $middleware
     */
    public function addMiddleware($middleware): void
    {
        $this->middlewareStack->addMiddleware($middleware);
    }

    /**
     * Add a new route to the app.
     *
     * @param array $methods
     * @param string $pattern
     * @param callable|string $handler
     * @return Route
     */
    public function map(array $methods, string $pattern, $handler): Route
    {
        return $this->routeRepository->map($methods, $pattern, $handler);
    }

    /**
     * Add a new route to the app.
     *
     * @param string $pattern
     * @param callable|string $handler
     * @return Route
     */
    public function get(string $pattern, $handler): Route
    {
        return $this->map(['GET'], $pattern, $handler);
    }
    /**
     * Add a new route to the app.
     *
     * @param string $pattern
     * @param callable|string $handler
     * @return Route
     */
    public function post(string $pattern, $handler): Route
    {
        return $this->map(['POST'], $pattern, $handler);
    }

    /**
     * Dispatch the request through middleware and routing.
     *
     * @param ServerRequestInterface|null $request
     */
    public function run(ServerRequestInterface $request = null): void
    {
        global $DIC;
        if (!$request) {
            $request = $DIC->http()->request();
        }
        $response = $this->middlewareStack->handle($request);
        $this->emit($response);

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
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;
        $this->routeRepository->setBasePath($basePath);
        return $this;
    }

    /**
     * @return RouteRepository
     */
    public function getRouteRepository(): RouteRepository
    {
        return $this->routeRepository;
    }

    /**
     * @param RouteRepository $routeRepository
     * @return App
     */
    public function withRouteRepository(RouteRepository $routeRepository): self
    {
        $this->routeRepository = $routeRepository;
        return $this;
    }

    /**
     * Emit the response to the client.
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            foreach ($response->getHeaders() as $name => $values) {
                $replace = strtolower($name) !== 'set-cookie';
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $replace);
                    $replace = false;
                }
            }

            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ), true, 400);
        }

        $this->http->saveResponse($response);
        $this->http->sendResponse();

    }

}