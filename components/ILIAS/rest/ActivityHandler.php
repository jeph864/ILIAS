<?php

namespace ILIAS\REST\Handlers;

use ILIAS\Component\Activities\Activity;


/**
 * Handles the complete lifecycle of an Activity:
 * - Resolving the route and mapping requests to activities.
 * - Validating input data.
 * - Dispatching activities and returning results.
 */
interface ActivityHandler
{
    /**
     * Validates the input parameters against the Activity's InputDescription.
     *
     * @param Activity $activity The activity being validated.
     * @param array $parameters The input parameters to validate.
     * @return bool True if the input is valid, false otherwise.
     */
    public function validate(array $parameters): mixed;

    /**
     * Sanitizes input parameters based on the Activity's InputDescription.
     *
     * @param Activity $activity The activity being sanitized.
     * @param array $parameters The raw input parameters.
     * @return array The sanitized parameters.
     */
    public function sanitize(array $parameters): array;

    /**
     * Dispatches the Activity with sanitized parameters and returns the result.
     *
     * @param Activity $activity The activity to dispatch.
     * @param array $parameters The input parameters to pass to the activity.
     * @return Result The result of the activity execution.
     */
    public function dispatch(array $parameters): mixed;


}