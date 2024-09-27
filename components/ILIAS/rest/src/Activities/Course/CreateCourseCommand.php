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

namespace ILIAS\Course\Activities;

use ilDatabaseException;
use ILIAS\Component\Activities\ActivityImpl;
use ILIAS\Component\Activities\Command;
use ILIAS\Component\Activities\Query;
use ILIAS\Data\Result;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ilObjCourse;
use ILIAS\Data\Factory;
use ilObjectFactory;
use ilObjectNotFoundException;

/**
 *
 */
class CreateCourseCommand extends Command
{
    use AbstractCourseActivity;
    public function __construct()
    {
        $this->factory = new Factory();
    }

    public function getName(): \ILIAS\Component\Dependencies\Name
    {
        return new \ILIAS\Component\Dependencies\Name(self::class);
    }


    public function getDescription(): string
    {
        return 'CreateCourse Command';
    }


    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return true;
    }


    public function perform(mixed $parameters): mixed
    {
        $GLOBALS['DIC']->logger()->root()->dump(array(
            $parameters['title'],
            $parameters['description'],
            $parameters['parent_id']

        ));
        $newCourse = new ilObjCourse();
        $newCourse->setType('crs');
        $newCourse->setTitle($parameters['title']);
        $newCourse->setDescription($parameters['description']);
        $newCourse->create();
        $newCourse->createReference();
        $newCourse->putInTree($parameters['parent_id']);
        $newCourse->setPermissions($parameters['parent_id']);
        return $this->factory->ok($newCourse->getRefId());

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

