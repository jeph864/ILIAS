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


class  ActionPayload implements ActionResponse
{


    public function __construct(
        protected mixed $output,
        protected $data = null,
        protected int $statusCode = 200,
        protected $error = null
    ) {

    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array|null|object
     */
    public function getData(): mixed
    {
        return $this->data;
    }
    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @return array
     */
    public function getContentType(): array
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }



    public function jsonSerialize(): array
    {
        return $this->data;
        //Ignore this for Now
        $payload = [
            'statusCode' => $this->statusCode,
        ];
        if ($this->data !== null) {
            $payload['data'] = $this->data;
        } elseif ($this->error !== null) {
            $payload['error'] = $this->error;
        }
        return $payload;
    }

    public function getErrors(): array
    {
        return $this->error;
    }

    public function cast(): self {

        // Well not yet sure how this sould look like. Do we need a schema???
        return $this;
    }

}



