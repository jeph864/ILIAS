<?php
require_once 'vendor/composer/vendor/autoload.php';


use Psr\Container\ContainerInterface;

// Mock container implementation
class Container implements ContainerInterface {
    private $entries = [];

    public function has($id): bool {
        return isset($this->entries[$id]);
    }

    public function get($id) {
        return $this->entries[$id] ?? null;
    }

    public function set($id, $value) {
        $this->entries[$id] = $value;
    }
}

// Example class
class ExampleHandler {
    public function exampleMethod() {
        return 'Hello from ExampleHandler::exampleMethod!';
    }
    public function __invoke() {
        return 'Hello from ExampleHandler::__invoke!';
    }
}

// Setup container
$container = new Container();
$container->set('ExampleHandler', new ExampleHandler());

// Resolve callables
$resolver = new \ILIAS\REST\Handlers\ActionResolver($container);

// Resolve a class:method callable
$callable = $resolver->resolve(ExampleHandler::class);
echo call_user_func($callable) . PHP_EOL; // Output: Hello from ExampleHandler::exampleMethod!

// Resolve a closure
$closure = $resolver->resolve(function ($request, $response, $args) {
    return 'Hello from a closure!';
});
echo $closure(1,2,3) . PHP_EOL; // Output: Hello from a closure!