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


interface ActionResponse
{
    /**
     * Get the response payload
     * @return mixed The response data
     */
    public function getData(): mixed;

    /**
     * Get response status code
     */
    public function getStatusCode(): int;

    /**
     * Get any error messages
     * @return array<string> List of error messages
     */
    public function getErrors(): array;

}



