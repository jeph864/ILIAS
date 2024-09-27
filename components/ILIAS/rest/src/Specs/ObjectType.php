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

namespace ILIAS\Specs\Type;


class ObjectType implements OutputType
{
    use TypeInternals;


    public function __construct(protected string $name, protected $properties, string $description = '', bool $required = false )
    {
        $this->description = $description;
        $this->required = $required;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toSchema(): mixed
    {
        // For REST: JSON Schema object type
        $properties = [];
        foreach ($this->properties as $key => $type) {
            $properties[$key] = $type->toSchema();
        }
        return [
            'type' => 'object',
            'properties' => $properties,
        ];


    }
}