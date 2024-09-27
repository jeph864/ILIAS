<?php

declare(strict_types=1);

namespace ILIAS\REST\Auth;

use Psr\Http\Message\ServerRequestInterface;

interface TokenService
{
    /**
     * Issue a new access token.
     */
    public function issueToken(ServerRequestInterface $request): array;

    /**
     * Validate an access token.
     */
    public function validateToken(string $token): bool;

    /**
     * Parse and retrieve claims from a token.
     */
    public function getClaims(string $token): array;

    /**
     * Revoke a given token.
     */
    public function revokeToken(string $token): bool;

    /**
     * Refresh an expired access token.
     */
    public function refreshToken(string $refreshToken): array;
}