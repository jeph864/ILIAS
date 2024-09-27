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

/**
 * Interface SchemaType
 *
 * This interface defines a schema structure that can be used for both REST (OpenAPI) and SOAP (WSDL).
 * It supports primitive types, composite (complex) types, and list (array) types.
 * The purpose is to generalize the schema definitions for different web services.
 */
interface SchemaType
{
    /**
     * Get the unique name of the schema type.
     *
     * Example:
     * - "User"
     * - "ArrayOfUsers"
     *
     * @return string The name of the schema type.
     */
    public function getName(): string;

    /**
     * Get a short description of the schema type.
     *
     * This provides a human-readable explanation of the schema's purpose.
     *
     * @return string The description in markdown?? format.
     */
    public function getDescription(): string;

    /**
     * Get the classification of the schema type.
     *
     * Possible values:
     * - "simple" (e.g., string, integer, boolean)
     * - "composite" (object structures with fields)
     * - "list" (arrays or lists of a base type)
     *
     * @return string The classification of the schema type.
     */
    public function getTypeClass(): string;

    /**
     * Get the data type represented by this schema.
     *
     * This typically corresponds to a programming language's data type.
     *
     * Example values:
     * - "string"
     * - "integer"
     * - "boolean"
     * - "object"
     * - "array"
     *
     * @return SimpleDataType The data type.
     */
    public function getDataType(): SimpleDataType;

    /**
     * Get the structural rule for this schema type.
     *
     * Used primarily for composite types to specify how elements are grouped.
     *
     * Possible values:
     * - "sequence" (elements appear in order)
     * - "choice" (only one element can appear)
     * - "all" (all elements appear but in any order)
     * - NULL (for primitive or list types)
     *
     * @return StructureRule|null The structure rule, or NULL if not applicable.
     */
    public function getStructureRule(): ?StructureRule;

    /**
     * Get the base restriction type if applicable.
     *
     * Used for defining restrictions on data types (e.g., SOAP-ENC:Array).
     *
     * Example values:
     * - "SOAP-ENC:Array"
     * - NULL (if no restriction applies)
     *
     * @return string|null The restriction base, or NULL if not applicable.
     */
    public function getRestrictionBase(): ?string;

    /**
     * Get the fields (or elements) of a composite schema type.
     *
     * This is used for objects that contain multiple named sub-elements.
     *
     * Example:
     * ```php
     * [
     *    "id" => new PrimitiveSchemaType("id", "integer"),
     *    "name" => new PrimitiveSchemaType("name", "string")
     * ]
     * ```
     *
     * @return array<string, SchemaType> A key-value mapping of field names to schema definitions.
     */
    public function getFields(): array;

    /**
     * Get metadata attributes for this schema type.
     *
     * Attributes provide additional metadata that is not part of the main data structure.
     * This is commonly used for XML attributes in SOAP but can also be used in JSON Schema.
     *
     * Example:
     * ```php
     * [
     *    "isAdmin" => new PrimitiveSchemaType("isAdmin", "boolean")
     * ]
     * ```
     *
     * @return array<string, SchemaType> A key-value mapping of attribute names to schema definitions.
     */
    public function getMetadata(): array;

    /**
     * Get the base item type if this schema represents a list or array.
     *
     * Used when the schema type is a listType.
     *
     * Example:
     * - "string[]" (array of strings)
     * - "User[]" (array of User objects)
     * - NULL (if not a list type)
     *
     * @return SchemaType|null The data type of the list elements, or NULL if not applicable.
     */
    public function getListItemType(): ?self;

    /**
     * Check if the schema type is required.
     *
     * @return bool True if the field is required, false otherwise.
     */
    public function isRequired(): bool;


    public function withName(string $name): self;

    public function withTypeClass(string $typeClass): self;

    public function withDataType(SimpleDataType $dataType): self;

    public function withStructureRule(?StructureRule $structureRule): self;

    public function withRestrictionBase(?string $restrictionBase): self;

    public function withField(string $fieldName, SchemaType $fieldSchema): self;

    public function withDescription(string $description): self;

    public function withMetadata(string $attributeName, SchemaType $attributeSchema): self;

    public function withListItemType(?SchemaType $listItemType): self;
    /**
     * Set whether the field is required.
     *
     * @param bool $required True if required, false otherwise.
     * @return self
     */
    public function withRequired(bool $required): self;

    /**
     * Apply an additional transformation or constraint.
     */
    public function withAdditionalTransformation(Transformation $transformation): self;

    public function toSchema(): array;
}