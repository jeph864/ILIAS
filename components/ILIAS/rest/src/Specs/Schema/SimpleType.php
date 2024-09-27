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


class Simple extends AbstractSchemaType
{
    public function __construct(string $name, SimpleDataType $dataType)
    {
        $this->withName($name)
            ->withTypeClass('simple')
            ->withDataType($dataType);
    }

    public function getStructureRule(): ?StructureRule
    {
        return null;
    }

    public function toSchema(): array
    {
        $schema =  [

            'type' => $this->dataType
        ];
        if ($this->isRequired()) {
            $schema['required'] = true;
        }
        if ($this->getDescription()) {
            $schema['description'] = $this->getDescription();
        }
        return array(
            $this->name => $schema);
    }
}