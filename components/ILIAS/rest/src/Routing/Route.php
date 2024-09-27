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

use ILIAS\REST\Middleware\MiddlewareStack;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ILIAS\REST\Handlers\ActionResolver;

class Route implements RequestHandlerInterface
{
    protected array $methods;
    protected string $pattern;
    protected $action;
    protected ResponseFactoryInterface $responseFactory;
    protected ActionResolver $actionResolver;
    protected array $arguments = [];
    protected array $savedArguments = [];
    protected string $identifier;
    protected MiddlewareStack $middlewareStack;
    protected string $name;

    public function __construct(
        array $methods,
        string $pattern,
        $action,
        ResponseFactoryInterface $responseFactory,
        ActionResolver $actionResolver,
        int $identifier = 0
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->action = $action;
        $this->responseFactory = $responseFactory;
        $this->actionResolver = $actionResolver;
        $this->identifier = 'route' . $identifier;
        $this->middlewareStack = new MiddlewareStack($this, $actionResolver);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    /**
     * Prepares the route by merging the provided arguments with saved arguments.
     *
     * @param array $arguments The arguments to prepare the route with.
     *
     * @return $this
     */
    public function withArguments(array $arguments): self
    {
        $this->arguments = $arguments + $this->savedArguments;
        return $this;

    }

    public function getArgument(string $name, ?string $default = null): ?string
    {
        return $this->arguments[$name] ?? $default;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): self
    {
        $this->savedArguments = $arguments;
        $this->arguments = $arguments;
        return $this;
    }

    public function setArgument(string $name, string $value): self
    {
        $this->savedArguments[$name] = $value;
        $this->arguments[$name] = $value;
        return $this;
    }

    public function add($middleware): self
    {
        $this->middlewareStack->add($middleware);
        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewareStack->addMiddleware($middleware);
        return $this;
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middlewareStack->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        $action = $this->actionResolver->resolve($this->action);
        $response = $this->responseFactory->createResponse();

        // Execute the action with the request, response, and arguments
        return $action($request, $response, $this->arguments);
    }
}