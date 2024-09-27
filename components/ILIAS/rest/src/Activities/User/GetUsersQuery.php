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

use ilDatabaseException;
use ILIAS\Component\Activities\ActivityImpl;
use ILIAS\Component\Activities\Query;
use ILIAS\Data\Result;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ilObjCourse;
use ILIAS\Data\Factory;
use ilObject;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjUser;
use ilUserQuery;
use ILIAS\UI\Implementation\Component\Input\DynamicInputsNameSource;

/**
 *
 */
class GetUsersQuery extends Query
{
    use AbstractUserActivity;

    public function getDescription(): string
    {
        return 'List User Query';
    }
    public function getName(): \ILIAS\Component\Dependencies\Name
    {
        return new \ILIAS\Component\Dependencies\Name(self::class);
    }

    public function getInputDescription(): \ILIAS\UI\Component\Input\Input
    {
        global $DIC;
        $ui_factory = $DIC->ui()->factory();
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
            ->withNameFrom(new DynamicInputsNameSource('g'));

        //return $this->ui_factory->input()->container()->form()->standard('', $inputs ); //withJsonRequest ---> kommt noch

    }

    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return true;
    }

    /**
     * @throws ilObjectNotFoundException
     * @throws ilDatabaseException
     */
    public function perform(mixed $parameters): mixed
    {

        /** @var ilObjUser $user */
        $ilias_course =  '';
        return ilObjUser::_getUserData(array($parameters['usr_id']));

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
}

