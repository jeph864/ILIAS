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

namespace ILIAS\REST;

use ILIAS\REST\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class  ActivityNamespace
{
    private const PROPER_NAME_REGEXP = "/\w+([\\\\]\w+){2,}/";

    private string $vendor;
    private string $component;
    private string $activity;

    public function __construct(string $namespace)
    {
        if (!preg_match(self::PROPER_NAME_REGEXP, $namespace)) {
            throw new \InvalidArgumentException(
                "{$namespace} is not a proper name for a dependency."
            );
        }
        $this->parseNamespace($namespace);
    }

    private function parseNamespace(string $namespace): void
    {
        // Split the namespace into parts
        $parts = explode('\\', $namespace);

        // Validate that the namespace is long enough
        if (count($parts) < 3) {
            throw new \InvalidArgumentException("Invalid namespace. Must contain at least vendor, component, and activity.");
        }

        // Assign values
        $this->vendor = $parts[0];
        $this->component = $parts[1];
        $this->activity = end($parts);
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getActivity(): string
    {
        return $this->activity;
    }
    public function getPrefix(): string
    {
        // Combine vendor and component
        return $this->vendor . '/' . $this->component;
    }


}



