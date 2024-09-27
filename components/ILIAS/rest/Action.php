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


interface Action
{
    /**
     * For documentation: This could be equivalent to a SOAP method name or REST API route name
     * @return string
     */
    public function getName(): string;
    /**
     * For documentation: Metadata
     * @return string
     */
    public function getDescription(): string;

    /**
     * For documentation: JSONSerializable
     * @return array
     */
    public function getInput(): array;

    /**
     * For documentation: JSONSerializable
     * @return array
     */
    public function getOutput(): array;
    /**
     * Checks if the action should be authenticated
     * @return bool
     */
    public function requiresAuthentication(): bool;
    /**
     * It authenticates a user. The service implementing this, provide the authentication logic
     * Options:
     *      - ILIAS SID
     *      - API Token
     *      - ---
     * @return bool
     */
    public function authenticate(): bool ;

    /**
     * Validate the request against some validation. Form?
     *
     * @param mixed $parameters
     * @return bool
     */
    public function validate(mixed $parameters): bool;
    /**
     * Executes an action(Queue???).
     * GET course/{ref_id}
     * @param mixed $parameters
     * @return mixed
     */
    public function execute(mixed $parameters): mixed;

    public function getMethod() : string;

    public function getPath() : string;

}



