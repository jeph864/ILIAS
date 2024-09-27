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

namespace ILIAS\Specs\Schema;

class CompositeType extends AbstractSchemaType
{

    public function __construct(string $name, array $properties, ?StructureRule $structureRule = null)
    {
        $this->withName($name)
            ->withTypeClass('composite')
            ->withDataType(SimpleDataType::OBJECT)
            ->withStructureRule($structureRule)
            ->withFields($properties);
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();

        $properties = [];
        $required = [];
        /**
         * @var SchemaType $field
         */
        foreach ($this->getFields() as $field) {
            $name = $field->getName();
            $properties[$name] = $field->toSchema()[$name];
            if($field->isRequired()) {
                $required = $field->getName();
            }
        }
        $schema['properties'] = $properties;
        if ($required) {
            $schema['required'] = $required;
        }
        return $schema;
    }
}