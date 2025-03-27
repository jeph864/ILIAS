<?php

namespace ILIAS\REST\Handlers;

use ILIAS\Component\Activities\Activity;
use ILIAS\Export\ImportStatus\Exception\ilException;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Group;
use ILIAS\UI\Component\Input\Input;
use ILIAS\UI\Implementation\Component\Input\ArrayInputData;
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
    protected int $usr_id;
    public function __construct(protected Activity $activity)
    {

    }

    public function __invoke(mixed $parameters)
    {
        $this->validate($parameters);
       if( $this->activity->isAllowedToPerform($this->usr_id, $parameters)) {
           $this->activity->perform($parameters);
       }
    }

    /**
     * Validates the input parameters against the Activity's InputDescription.
     *
     * @param array $parameters The input parameters to validate.
     * @return bool True if the input is valid, false otherwise.
     */
    public function validate(array $parameters): mixed
    {
        global $DIC;

        $inputs = $this->activity->getInputDescription();
        $form = $DIC->ui()->factory()->input()->container()->form()->standard('', $inputs->getInputs());
        $template = $form->foldWith(
            function (\ILIAS\UI\Component\Component $c){
                $subs = $c->getSubStructure();
                if ($subs != null) {
                    return $subs;
                }else {
                    return '';
                }
            }
        );
        $parameters = $this->initializeMissingValues($template, $this->transform($parameters));
        $form = $form->withInput(new ArrayInputData($parameters));

        if ($form->getError()) {
            $errors = array_map( fn($comp) => $comp->getError(), $form->getInputs());
            foreach ($errors as $key => $message)
            {
                if($message)
                throw new ilException("Error in the input. {$key}: {$message}");
            }
        }
        return $parameters;

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

    private function initializeMissingValues(array $template, array $userInput): array {
        foreach ($template as $key => $value) {
            if (is_array($value)) {
                $userInput[$key] = $this->initializeMissingValues($value, $userInput[$key] ?? []);
            } else {
                if (!array_key_exists($key, $userInput)) {
                    $userInput[$key] = '';
                }
            }
        }
        return $userInput;
    }

    private function transform(array $array): array {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[$key] = is_array($value) ? $this->transform($value) : $value;
            } else {
                $newKey = 'form/' . $key;
                $result[$newKey] = is_array($value) ? $this->transform($value) : $value;
            }
        }
        return $result;
    }




}