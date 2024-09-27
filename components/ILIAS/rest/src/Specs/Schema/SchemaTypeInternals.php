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

use ILIAS\Refinery\Transformation;
use ILIAS\Specs\Type\ObjectType;
use InvalidArgumentException;

trait SchemaTypeInternals
{

    protected string $name;
    protected string $typeClass;
    protected string $description =  '';
    protected bool $required = false;
    protected SimpleDataType $dataType;
    protected ?StructureRule $structureRule = null;
    protected ?string $restrictionBase = null;
    protected array $fields = [];
    protected array $metadata = [];
    protected ?SchemaType $listItemType = null;
    protected array $transformations = [];

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withTypeClass(string $typeClass): self
    {
        $this->typeClass = $typeClass;
        return $this;
    }

    public function withDataType(SimpleDataType $dataType): self
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function withStructureRule(?StructureRule $structureRule): self
    {
        $this->structureRule = $structureRule;
        return $this;
    }

    public function withRestrictionBase(?string $restrictionBase): self
    {
        $this->restrictionBase = $restrictionBase;
        return $this;
    }

    public function withField(string $fieldName, SchemaType $fieldSchema): self
    {
        $this->fields[$fieldName] = $fieldSchema;
        return $this;
    }
    public function withFields(array $fields): self
    {
        foreach ($fields as $field){
            if (!$field instanceof SchemaType) {
                throw new InvalidArgumentException("Fields should be of type SchemaType");
            }
        }
        $this->fields = $fields;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }


    public function withMetadata(string $attributeName, SchemaType $attributeSchema): self
    {
        $this->metadata[$attributeName] = $attributeSchema;
        return $this;
    }

    public function withListItemType(?SchemaType $listItemType): self
    {
        $this->listItemType = $listItemType;
        return $this;
    }


    public function withAdditionalTransformation(Transformation $transformation): self
    {
        return $this;
    }

    public function isRequired(): bool
    {
       return $this->required;
    }

    public function withRequired(bool $required): SchemaType
    {
       $this->required = $required;
       return $this;
    }



    public  function getName(): string { return  $this->name; }

    public function getTypeClass(): string { return $this->typeClass; }

    public abstract function getDataType(): SimpleDataType;

    public function getStructureRule(): ?StructureRule { return $this->structureRule; }

    public function getRestrictionBase(): ?string { return $this->restrictionBase; }

    public function getFields(): array { return $this->fields; }

    public function getMetadata(): array { return $this->metadata; }

    public  function getListItemType(): ?SchemaType
    {
        return  $this->listItemType;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTransformations(): array { return $this->transformations; }
}