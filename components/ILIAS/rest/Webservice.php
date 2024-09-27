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


interface Webservice
{
    /**
     * Get the protocol type (REST, SOAP, etc.)
     */
    public function getProtocol(): string;

    /**
     * Handle an incoming request
     * @param mixed $request Protocol-specific request object
     * @return mixed Protocol-specific response object
     */
    public function handle(mixed $request): mixed;

    /**
     * Map request to an action
     * @param mixed $request Protocol-specific request object
     * @return Action
     * @throws ActionNotFoundException
     */
    public function resolveAction(mixed $request): Action;

    /**
     * Generate service documentation
     * @return string Documentation in appropriate format (OpenAPI, WSDL, etc.)
     */
    public function getDocumentation(): mixed;

}



