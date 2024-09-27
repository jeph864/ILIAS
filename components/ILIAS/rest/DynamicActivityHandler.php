<?php

namespace ILIAS\REST\Handlers;

use ILIAS\Component\Activities\Activity;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles the complete lifecycle of an Activity:
 * - Resolving the route and mapping requests to activities.
 * - Validating input data.
 * - Dispatching activities and returning results.
 */
class DynamicActivityHandler implements ActivityHandler
{
    public function __construct(protected Activity $activity)
    {
    }
    public function __invoke(Request $request, Response $response, array $args)
    {
    }

    /**
     * Validates the input parameters against the Activity's InputDescription.
     *
     * @param array $parameters The input parameters to validate.
     * @return bool True if the input is valid, false otherwise.
     */
    public function validate(array $parameters): bool
    {
        $form = $this->activity->getInputDescription();
        $form->withJson($parameters);

        $GLOBALS['DIC']->logger()->root()->dump(array($form->getData()));
        return  true;
    }

    /**
     * Sanitizes input parameters based on the Activity's InputDescription.
     *
     * @param array $parameters The raw input parameters.
     * @return array The sanitized parameters.
     */
    public function sanitize(array $parameters): array
    {
    }
    /**
     * You might not need this. This should be in routers. The idea is to provide a route given an activiy
     */
    public function resolve()
    {
    }

    /**
     * Dispatches the Activity with sanitized parameters and returns the result.
     * This would be a wrapper to an activities 'perform' or 'performAs'.
     *
     * @param array $parameters The input parameters to pass to the activity.
     * @return mixed The result of the activity execution.
     */
    public function dispatch(array $parameters): mixed
    {
    }


}