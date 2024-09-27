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
use ILIAS\Specs\Schema\SchemaType;
use ILIAS\Specs\Schema\Simple;
use ILIAS\Specs\Type\TypeFactory;
use ILIAS\Specs\Type\ObjectType;
use ILIAS\UI\Component\Input\Field\Json;
use ilSetting;
use nusoap_fault;
use soap_server;


class  SoapApp implements Webservice
{
    public soap_server $server;

    const SERVICE_NAME = 'ILIASWebservice';
    const SERVICE_NAMESPACE =  'urn:ActivitiesAPI';
    const  SERVICE_STYLE = 'rpc';
    const SERVICE_USE = 'encoded';
    public function __construct(protected \ILIAS\Component\Activities\Repository $activities_registry)
    {
        $this->server = new soap_server();
        $this->server->decode_utf8 = false;
    }



    private function registerMethods()
    {
        /**
         * @var Activity $activity
         */
        foreach ($this->activities_registry->getActivitiesByName('/.*/') as $name => $activity){
            $namespace = new ActivityNamespace($name);//Rely on components to get this information

            $this->server->register(
                (string) $activity->getName(),
                $this->generateInputSpecs($activity),
                $this->generateOutputSpecs($activity),
                self::SERVICE_NAME,
                self::SERVICE_NAME . '#' . (string) $activity->getName(),
                self::SERVICE_STYLE,
                self::SERVICE_USE

            );


        }


    }

    private function enableWSDL(ilSetting $setting): void
    {
        $this->server->configureWSDL(SERVICE_NAME, SERVICE_NAMESPACE);
        $internal_path = $setting->get('soap_internal_wsdl_path', '');
        if ($internal_path) {
            $this->server->addInternalPort(SERVICE_NAME, $internal_path);
        }
    }

    public function getProtocol(): string {
        return 'SOAP';
    }
    /**
     * Handle SOAP requests
     */
    public function handle(mixed $request) :mixed
    {
        $this->server->service(file_get_contents("php://input"));
    }

    /**
     * Magic method to dynamically route SOAP calls to the correct Activity
     */
    public function __call($method, $arguments) {
        $activity = $this->activities_registry->getActivitiesByName($method);
        if ($activity instanceof Activity) {

            return $activity->perform($arguments);
        }
        return new nusoap_fault("Client", "", "Method $method not found");
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
    public function getDocumentation(): string
    {

    }

    public function register(Activity $activity)
    {

    }
    public function registerType(SchemaType $schema)
    {
        if(!$schema instanceof Simple){

        }
    }
    private  function generateInputSpecs(Activity $activity): array
    {
    }

    private function generateOutputSpecs(Activity $activity): array
    {
    }


}



