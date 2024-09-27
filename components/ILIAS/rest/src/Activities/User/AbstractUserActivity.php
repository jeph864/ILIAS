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

declare(strict_types=1);

namespace ILIAS\User\Activities;


use ILIAS\Data\Result;
use ILIAS\Specs\Schema\SchemaType;
use ILIAS\Specs\Type\ObjectType;
use ILIAS\UI\Component\Input\Input;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use ILIAS\Data\Factory;
use ilObjUser;

/**
 *
 */
trait  AbstractUserActivity
{

    protected $dic;

    public function getInputDescription(): Input
    {
    }

    public function getOutDescription(): SchemaType
    {
        return (new \ILIAS\Specs\Schema\Factory())->int('some_value');
    }


    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return true;
    }


    public function updateUserObject($data, ilObjUser $user): ilObjUser
    {

        foreach ($data as $key => $value) {
            if ($value == null){
                continue;
            }
            switch ($key){
                case 'login':
                    $user->setTitle($value);
                    break;
                case 'auth_mode':
                    $user->setAuthMode($value);
                    break;
                case 'gender':
                    $user->setGender($value);
                    break;
                case 'firstname';
                    $user->setFirstname($value);
                    break;
                case 'institution':
                    $user->setInstitution($value);
                    break;
                case 'department':
                    $user->setDepartment($value);
                    break;
                case 'street':
                    $user->setStreet($value);
                    break;
                case 'city':
                    $user->setCity($value);
                    break;
                case 'country':
                    $user->setCountry($value);
                    break;
                case 'zipcode':
                    $user->setZipcode($value);
                    break;
                case 'email':
                    $user->setEmail($value);
                    break;
                case 'second_email':
                    $user->setSecondEmail($value);
                    break;
                case 'active':
                    $user->setActive($value);
                    break;
                case 'user_defined_data':
                    break;
                case 'time_limit_unlimited':
                    $user->setTimeLimitUnlimited($value);
                    break;
                case 'time_limit_from':
                    $user->setTimeLimitFrom($value);
                    break;
                case 'time_limit_until':
                    $user->setTimeLimitUntil($value);
                    break;
                case 'time_limit_owner':
                    $user->setTimeLimitOwner($value);
                    break;
                case 'time_limit_message':
                    $user->setTimeLimitMessage($value);
                    break;

                default:
                    //
            }
        }
        return $user;
    }

    public function maybePerformAs(int $usr_id, array $raw_parameters): Result
    {
        $data_factory = new Factory();
        if($this->isAllowedToPerform($usr_id, $raw_parameters)) {
            $results = $this->perform($raw_parameters);
            return $data_factory->ok($results);
        }
        return $data_factory->error('Failed');
    }
    public function getCheckedInputDescription(): Input
    {
        // TODO: Implement getCheckedInputDescription() method.
    }

    public function getTargetType(): string
    {
        return 'usr';
    }
}

