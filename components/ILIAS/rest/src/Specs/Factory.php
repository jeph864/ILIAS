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


class Factory {
    public function int(string $name = ''): SchemaType
    {
        return new Simple($name, SimpleDataType::INTEGER);
    }
    public function numeric(string $name = ''): SchemaType
    {
        return new Simple($name, SimpleDataType::NUMERIC);
    }
    public function string(string $name = ''): SchemaType
    {
        return new Simple($name, SimpleDataType::STRING);
    }
    public function float(string $name = ''): SchemaType
    {
        return new Simple($name, SimpleDataType::FLOAT);
    }
    public function boolean(string $name = ''): SchemaType
    {
        return new Simple($name, SimpleDataType::BOOLEAN);
    }

    public function composite(string $name, $fields, ?StructureRule $structureRule = null): SchemaType
    {
        return new CompositeType($name, $fields, $structureRule);
    }
    public function list(string $name , SchemaType $schemaType, ): SchemaType
    {
        return new ListType($name, $schemaType);
    }

}