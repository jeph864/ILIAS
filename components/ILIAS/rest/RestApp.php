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

use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use ILIAS\Component\Activities\ObjectActivity;
use ILIAS\Component\Activities\Query;
use ILIAS\Data\Description\Description;
use ILIAS\Data\Factory;
use ILIAS\Data\Text\SimpleDocumentMarkdown;
use ILIAS\REST\Handlers\DynamicActivityHandler;
use ILIAS\REST\Middleware\AuthMiddleware;
use ILIAS\Specs\Schema\SchemaType;
use ILIAS\Specs\Type\ArrayType;
use ILIAS\Specs\Type\TypeFactory;
use ILIAS\Specs\Type\ObjectType;
use ILIAS\Specs\Type\PrimitiveType;
use ILIAS\UI\Component\Input\Field\Json;
use ILIAS\UI\Component\Input\Input;
use ILIAS\UI\Implementation\Component\Input\DynamicInputsNameSource;
use ILIAS\UI\Implementation\Component\Input\FormInputNameSource;
use ILIAS\UI\Implementation\Component\Input\PostDataFromServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ILIAS\REST\App;
use ILIAS\UI\Implementation\Component\Input\ArrayInputData;

class  RestApp implements Webservice
{
    private array $routes = [];
    private array $pathsSpecs;
    private  \ILIAS\Data\Description\Factory $description_factory ;

    protected int $usr_id;
    public function __construct(protected App $app, protected \ILIAS\Component\Activities\Repository $activities_registry)
    {
        $this->description_factory = new \ILIAS\Data\Description\Factory();
        $this->registerRoutes();
        $this->pathsSpecs = [
            'paths' => []
        ];


    }

    public function getProtocol(): string {
        return 'REST';
    }
    /**
     * official Handler of an incoming request
     * @param mixed $request Protocol-specific request object
     * @return mixed Protocol-specific response object
     */
    public function handle(mixed $request): mixed
    {
    }

    public function run(): void
    {
        $this->app->run();
    }

    /**
     * Internal Handler of an incoming request
     * @return mixed Protocol-specific response object
     */
    public function handleAction(Activity $activity, Request $request, Response $response, array $args ) : Response
    {

        if(array_key_exists('id', $args)){
            $args ['id'] = (int) $args ['id'];
        }
        if($activity instanceof Query && strtolower($request->getMethod()) === 'post' ) {
            //throw invalid method
        }
        $params = array_merge($args, $request->getParsedBody() ?? [], $request->getQueryParams() ?? []);
        $parameters = $this->validate($params);
        if($activity->isAllowedToPerform($this->usr_id, $parameters)) {
            $result = $activity->perform($parameters);
        }
        $payload = new ActionPayload(
            $activity->getOutputDescription($this->description_factory),
            $result
        );
        $response->getBody()->write(json_encode($payload->cast()->jsonSerialize()));
        foreach ($payload->getHeaders() as $name => $value) {
            $response->withHeader($name, $value);
        }
        return $response->withHeader('Content-Type', 'application/json');

    }

    /**
     * Map request to an action
     * @param mixed $request Protocol-specific request object
     * @return Action
     * @throws ActionNotFoundException
     */
    public function resolveAction(mixed $request): Action
    {
    }

    /**
     * Generate service documentation
     * @return string Documentation in appropriate format (OpenAPI, WSDL, etc.)
     */
    public function getDocumentation(): mixed
    {

        $schema = $this->pathsSpecs;
        $schema['info'] = [
        'title' => 'ILIAS REST API',
            'description' => 'sfasfasf'
        ];
        return $schema;
    }

    public function registerRoutes(): void
    {
        $data_factory = new \ILIAS\Data\Factory();
        static $routes = [];
        //Auto-register routes based on actions
        foreach ($this->activities_registry->getActivitiesByName('/.*/') as $name => $activity){
            $namespace = new ActivityNamespace($name);//Rely on components to get this information

            $route = strtolower('/'. $namespace->getPrefix()) . '/';
            if ($activity instanceof ObjectActivity){
                $route = $route . "{id}/";
            }
            $route = $route . str_replace('Query', '', ucfirst($namespace->getActivity()));
            $method = $activity->getType() === ActivityType::Query ? 'GET' : 'POST';

            if ($this->isRouteRegistered($route, $method)){
                continue;
            }
            $this->app->map([$method], $route, function (
                Request $request,
                Response $response,
                array $args
            ) use ($activity) {
                return $this->handleAction($activity, $request, $response, $args);
            });

            $this->routes [] =  [$route => $method];
            // Generate API specs
            $input_specs = self::generateInputSpecs($activity->getInputDescription());
            $this->pathsSpecs['paths'][$route] = [
                strtolower($method) => [
                    'summary' => $activity->getDescription()->getRawRepresentation(),
                    'operationId' => (string) $activity->getName(),
                    'requestBody' =>  $input_specs,
                    'responses' => $this->generateOutputSpecs($activity->getOutputDescription(
                        $this->description_factory))
                ]
            ];
        }

        $documentation = $this->getDocumentation();
        $this->app->get('/api/docs', function (Request $request, Response $response, array $args) use ($documentation){
            $response->getBody()->write(json_encode($documentation));
            return $response->withHeader('Content-Type', 'application/json');
        });

    }


    public function isRouteRegistered(string $route, string $method): bool
    {
        $existingRoutes = $this->app->getRouteRepository()->getRoutes();
        foreach ($existingRoutes as $existingRoute) {
            if ($existingRoute->getPattern() === $route && in_array($method, $existingRoute->getMethods())) {
                return true; // Route is already registered
            }
        }

        return false; // Route is not registered
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

    /**
     * Some considerations:
     *  1. the form lacks the logic to handle nested objects. A group is a logical collection of objects but in reality
     *    this is a flat array with a logical but not a structural grouping.
     *
     *  2. You can't describe all the inputs at a granular level(at least not at once). though it is desired. The user of your component
     *     needs a possibility to document inputs, and outputs in steps. Take for example ``` ilObjUser::_getUserData(array($parameters['id']))```
     *     Which returns user data. Let's assume we have an API call which updates this data. If one wanted to be verbose
     *     they would create an Input object(maybe a json) which describes what each field does or its type. In some cases,
     *     You simply want to communicate thar the API expects some user info(maybe with some attributes). In this case you need a generic container
     *     object(maybe a json)
     *  3. For nested objects you probably need to parse a tree of components. These components can be json objscts themselves
     *    which also have similar keys but in different objects. currently my implementation of json does not handle ke duplication(issues coming from Namesource)
     *  4. Defaults??: Form inputs from browsers are a bit different from json inputs from webservices(rest, soap). Most form
     *     inputs are rendered and if not filled out, it's up to the application to discard these values. But
     *     these values are all submitted. For JSON/xml APIs one might submit a small subset of inputs(especially if
     *     if they are optional). Current implementation will complain if some keys are missing/assigned null.
     *  5. Currently there is no way to work with arrays(both at input, data and transformation level). One can extend JsonGroups but then you'd need to process
     *    your InputData differently. One call implement another group logic which works well with arrays. There are questions as to how this translates
     *    to the specification and transformations
     *  6. Output description is still problematic.
     *      a. There are issues with schemas, validations and transformations if any at all.
     *      b. How do we represent and reference other schemas? think of an array of similar objects
     *
     *
     * @param Request $request
     */

    public  function parseInputs(Request $request)
    {
        //$request = $request->withParsedBody(array_merge($request->getParsedBody() ?? [], ['id']));
        global $DIC;
        $ui_factory = $DIC->ui()->factory();
        $name_source = new DynamicInputsNameSource('g');
        $refinery = $DIC->refinery();
        $inputs = [
            'id' => $ui_factory->input()->field()->numeric('Course reference ID')
                ->withAdditionalTransformation($refinery->int()->isGreaterThan(0))
                ->withDedicatedName('id')
            //->withAdditionalTransformation($this->refinery->logical()->logicalOr()),
            ,
            'parent' => $ui_factory->input()->field()->numeric('Dummy parentID for testing validation')
                ->withAdditionalTransformation($refinery->int()->isGreaterThan(300))
                //->withAdditionalTransformation($this->refinery->int()->isLessThan(200))
                ->withDedicatedName('parent')

                ->withRequired(false),
            'some' => $ui_factory->input()->field()->numeric('some string')
                ->withDedicatedName('someElse')
                ->withAdditionalTransformation($refinery->int()->isLessThan(1000))
                ->withRequired(false),
            'f6' => $ui_factory->input()->field()->text("f5")
                ->withDedicatedName('f6')
                ->withRequired(false),
            'gen2' => $ui_factory->input()->field()->json([])
            ->withDedicatedName('gen2')
            ->withRequired(false),

            'f4' => $ui_factory->input()->field()->json([])
                ->withDedicatedName('f4')
                ->withRequired(false),
            'container' => $ui_factory->input()->field()->json([
                'f1' => $ui_factory->input()->field()->numeric('f1')
                    ->withDedicatedName('f1')
                    ->withAdditionalTransformation($refinery->int()->isLessThan(1000))
                    ->withRequired(false),
                'f2' => $ui_factory->input()->field()->text("f2")
                    ->withDedicatedName('f2')
                    ->withRequired(false),
                'f10' => $ui_factory->input()->field()->json([
                    'f5' => $ui_factory->input()->field()->text('f5')
                                ->withDedicatedName('f5')
                                ->withRequired(false),
                    'gen' => $ui_factory->input()->field()->json([])
                    ->withDedicatedName('gen')
                        ->withRequired(false)
                    ],

                ),

               
            ])->withNameFrom(new DynamicInputsNameSource('container'))
            ->withRequired(true)
                //->withAdditionalTransformation($refinery->))


        ];


        return $ui_factory->input()->field()->json(
            $inputs, 'group'
        )->withDedicatedName('form')
            ->withNameFrom(new DynamicInputsNameSource('g'))
            ->withInput(new PostDataFromServerRequest($request));

    }

    public function parseOutputs()
    {
        $types = new TypeFactory();
        return $types->object('User', [
            'id' => $types->primitive('integer'),
            'name' => $types->primitive('string'),
            'tags' => $types->array($types->primitive('string'))
        ]);


    }

    // Generate input specs using walk and describeInput
    protected static function generateInputSpecs($inputDescription)
    {
        return  array(
            'description' => $inputDescription->getLabel(),
            'content' => [
                'application/json' => [
                    'schema' => self::walk($inputDescription)
                ]
            ]
        );

    }

// Generate output specs using walk
    protected  function generateOutputSpecs(Description $outputDescription)
    {

        return [
            '200' => [
                'description' => 'Successful response',
                'content' => [
                    'application/json' => [
                        'schema' => $outputDescription->getDescription()->getRawRepresentation()
                    ]
                ]
            ]
        ];
    }

    /**
     * Walk through the JSONGroup and build a description.
     *
     * @param JSON $jsonGroup
     * @return ObjectType
     */
    public static function walk(Input $jsonGroup): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];
        //return $schema;

        foreach ($jsonGroup->getInputs() as $key => $input) {
            $schema['properties'][$key] = self::describeInput($input);
        }

        return $schema;
    }

    /**
     * Describe an individual input, extracting its type, label, and metadata.
     *
     * @param mixed $input
     * @return mixed
     */
    protected static function describeInput($input)
    {
        // Get the type from the class name
        $className = get_class($input);
        $type = self::mapClassToType($className);
        // Base schema for the input
        $schema = [
            'type' => $type,
        ];
        // Add the label (description) if available
        if (method_exists($input, 'getLabel')) {
            $schema['description'] = $input->getLabel();
        }

        // Add required status if available
        if (method_exists($input, 'isRequired') && $input->isRequired()) {
            $schema['required'] = true;
        }


        // Handle JSONGroup (nested objects)
        if ($input instanceof Json) {
            $nestedSchema = self::walk($input); // Recursive call for nested groups
            return array_merge($schema, $nestedSchema);

        }

        // Return a PrimitiveType with additional metadata
        return $schema;
    }
    /**
     * Map a class name to a generalized type.
     *
     * @param string $className
     * @return string
     */
    protected static function mapClassToType(string $className): string
    {
        if (str_contains($className, 'Numeric')) {
            return 'integer';
        }

        if (str_contains($className, 'Text')) {
            return 'string';
        }

        if (str_contains($className, 'Json')) {
            return 'object';
        }

        return 'string'; // Default to string if unknown
    }


}



