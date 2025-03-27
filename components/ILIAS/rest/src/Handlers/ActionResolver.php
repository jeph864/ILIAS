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

namespace ILIAS\REST\Handlers;


use Psr\Container\ContainerInterface;
use RuntimeException;

class ActionResolver
{
    public const CALLABLE_PATTERN = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    public function __construct(protected ContainerInterface $container)
    {

    }
    public function resolve($toResolve): callable|array
    {
        if (is_callable($toResolve)) {
            return $toResolve;
        }

        $resolved = $toResolve;

        if (is_string($toResolve)) {
            if (preg_match(self::CALLABLE_PATTERN, $toResolve, $matches)) {
                return [$matches[1], $matches[2]];
            }

            list($class, $method) =  [$toResolve, '__invoke'];

            //resolve the action
            if ($this->container->has($class)) {
                $resolved =  [$this->container->get($class), $method];
            }else if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Callable %s does not exist', $class));
            }else $resolved = [new $class($this->container), $method];
        }

        //make sure our action is resolvable
        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($resolved) || is_object($resolved) ? json_encode($resolved) : $resolved
            ));
        }
        return $resolved;
    }
}