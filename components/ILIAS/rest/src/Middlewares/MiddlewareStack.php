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

use Closure;
use ILIAS\REST\Handlers\ActionResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Handles middleware execution in a Last-In-First-Out (LIFO) stack.
 */
class MiddlewareStack implements RequestHandlerInterface
{
    protected RequestHandlerInterface $finalHandler;
    protected ?ActionResolver $actionResolver;
    protected ?ContainerInterface $container;

    public function __construct(
        RequestHandlerInterface $finalHandler,
        ?ActionResolver $actionResolver = null,
        ?ContainerInterface $container = null
    ) {
        $this->seedMiddlewareStack($finalHandler);
        $this->actionResolver = $actionResolver;
        $this->container = $container;
    }

    public function seedMiddlewareStack(RequestHandlerInterface $finalHandler): void
    {
        $this->finalHandler = $finalHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->finalHandler->handle($request);
    }

    public function add($middleware): self
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $this->addMiddleware($middleware);
        }

        if (is_callable($middleware)) {
            return $this->addCallableMiddleware($middleware);
        }

        throw new RuntimeException(
            'Middleware must be an instance of MiddlewareInterface or  a callable'
        );
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $next = $this->finalHandler;
        $this->finalHandler = new class ($middleware, $next) implements RequestHandlerInterface {
            private MiddlewareInterface $middleware;
            private RequestHandlerInterface $next;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->next);
            }
        };

        return $this;
    }


    public function addCallableMiddleware(callable $middleware): self
    {
        $next = $this->finalHandler;

        if ($this->container && $middleware instanceof Closure) {
            $middleware = $middleware->bindTo($this->container);
        }

        $this->finalHandler = new class ($middleware, $next) implements RequestHandlerInterface {
            private $middleware;
            private RequestHandlerInterface $next;

            public function __construct(callable $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->middleware)($request, $this->next);
            }
        };

        return $this;
    }
}