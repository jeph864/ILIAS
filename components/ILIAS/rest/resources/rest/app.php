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


use ILIAS\REST\Handlers\ActionResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\REST\RestApp;

global $DIC;

class Container1 implements ContainerInterface {
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

class ResponseFactory implements \Psr\Http\Message\ResponseFactoryInterface {

    public function createResponse(int $code = 200, string $reasonPhrase = ''): Response
    {
        global $DIC;
        return new \GuzzleHttp\Psr7\Response();
    }
}

$app = new \ILIAS\REST\App(
    new \ILIAS\REST\Routing\RouteRepository( new ActionResolver(new Container1()),
        new ResponseFactory(),
    )
);
$app->setBasePath('/rest');

$collection = new \ILIAS\Component\Activities\StaticRepository(
    array(
        new \ILIAS\Course\Activities\ListCoursesQuery(),
        new \ILIAS\Course\Activities\GetCourseQuery(),
        new \ILIAS\Course\Activities\UpdateCourseSettings(),
        new \ILIAS\Course\Activities\CreateCourseCommand(),
        new \ILIAS\User\Activities\GetUserQuery(),
        new \ILIAS\User\Activities\GetUsersQuery(),
        new \ILIAS\User\Activities\CreateUserCommand(),
        new \ILIAS\User\Activities\UpdateUserSettings()
    )
);

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$server = new RestApp($app, $collection);
//$routeRepository= $app->getRouteRepository();
//$routeRepository->setCacheFile('/tmp/rest.cache');
$server->run();
