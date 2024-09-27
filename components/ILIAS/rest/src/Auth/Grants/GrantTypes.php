<?php

declare(strict_types=1);

namespace ILIAS\REST\Auth\Grants;

use Psr\Http\Message\ServerRequestInterface;

interface GrantTypeI
{
    /**
     * Process the grant request and return an access token.
     */
    public function handle(ServerRequestInterface $request): array;

    /**
     * Return the grant type name (e.g., "authorization_code", "client_credentials").
     */
    public function getGrantType(): string;
}