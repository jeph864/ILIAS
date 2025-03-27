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

namespace ILIAS\REST\Middleware;

use ilAuthFrontendCredentials;
use ilAuthFrontendFactory;
use ilAuthProviderFactory;
use ilAuthStatus;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class AuthMiddleware implements MiddlewareInterface
{

    public function __construct(
        protected ResponseFactoryInterface $responseFactory
    )
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        global $DIC;
        // Check if the Authorization header exists
        $authHeader = $request->getHeaderLine('Authorization');


        if (empty($authHeader) || !str_starts_with($authHeader, 'Basic ')) {
            return $this->unauthorizedResponse();
        }

        // Extract and decode Basic Auth credentials
        $decodedCredentials = $this->getDecodedCredentials($authHeader);
        if (!$decodedCredentials) {
            return $this->unauthorizedResponse();
        }

        [$username, $password] = $decodedCredentials;
        $credentials = new ilAuthFrontendCredentials();
        $credentials->setUsername($username);
        $credentials->setPassword($password);

        $provider_factory = new ilAuthProviderFactory();
        $providers = $provider_factory->getProviders($credentials);

        $status = ilAuthStatus::getInstance();

        $frontend_factory = new ilAuthFrontendFactory();
        $frontend_factory->setContext(ilAuthFrontendFactory::CONTEXT_WS);
        $frontend = $frontend_factory->getFrontend(
            $DIC['ilAuthSession'],
            $status,
            $credentials,
            $providers
        );

        $frontend->authenticate();

        switch ($status->getStatus()){

            case ilAuthStatus::STATUS_AUTHENTICATED:
                $DIC->logger()->root()->dump(array("Logged In"));
                // Attach authenticated user to the request and proceed
                $request = $request->withAttribute('authenticated_user', $username);
                return $handler->handle($request);
            case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
                return $this->unauthorizedResponse();


        }

    }

    private function getDecodedCredentials(string $authHeader): ?array
    {
        $base64Credentials = substr($authHeader, 6); // Remove "Basic " prefix
        $decodedCredentials = base64_decode($base64Credentials);

        if (!$decodedCredentials || !str_contains($decodedCredentials, ':')) {
            return null;
        }

        return explode(':', $decodedCredentials, 2);
    }

    private function isValidCredentials(?string $username, ?string $password): bool
    {
        return isset($this->validCredentials[$username]) &&
            $this->validCredentials[$username] === $password;
    }

    private function unauthorizedResponse(): Response
    {
        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function authenticate()
    {

    }
}


