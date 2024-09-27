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
use ILIAS\Component\Activities\ObjectActivity;
use ILIAS\Component\Activities\Query;
use ILIAS\Data\Result;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Input;
use ILIAS\User\Activities\AbstractUserActivity;
use ilObjCourse;
use ILIAS\Data\Factory;
use ilObjectFactory;
use ilObjectNotFoundException;

/**
 *
 */
class GetCourseQuery extends Query implements ObjectActivity
{
    use AbstractCourseActivity;
    protected  \ILIAS\UI\Factory $ui_factory;
    protected \ILIAS\Refinery\Factory $refinery;
    public function __construct()
    {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->refinery = $DIC->refinery();

    }
    public function getName(): \ILIAS\Component\Dependencies\Name
    {
        return new \ILIAS\Component\Dependencies\Name(self::class);
    }

    public function getDescription(): string
    {
        return 'GetCourseQuery';
    }

    public function getInputDescription(): Input
    {
        return $this->ui_factory->input()->field()->json( [
            'id' => $this->ui_factory->input()->field()->numeric('Course reference ID')
            ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(0)),
            'parent' => $this->ui_factory->input()->field()->numeric('Dummy parentID for testing validation')
                ->withAdditionalTransformation($this->refinery->int()->isGreaterThan(300))
                ->withAdditionalTransformation($this->refinery->int()->isLessThan(200))
        ]); //withJsonRequest ---> kommt noch
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


        /** @var ilObjCourse $ilias_course */
        $ilias_course =  ilObjectFactory::getInstanceByRefId($parameters['ref_id']);
        return [
            "title" => $ilias_course->getTitle(),
            "ref_id" => $ilias_course->getRefId(),
            //"parent_id" => $ilias_course->
            "description" => $ilias_course->getDescription(),
            "owner" => $ilias_course->getOwner(),
            //availability
            "availability" => !$ilias_course->getOfflineStatus(),

            //syllabus
            "syllabus" => $ilias_course->getSyllabus(),
            "contact_consultation" => $ilias_course->getContactConsultation(),
            "contact_phone" => $ilias_course->getContactPhone(),
            "contact_email" => $ilias_course->getContactEmail(),
            "contact_name" => $ilias_course->getContactName(),
            "contact_responsibility" => $ilias_course->getContactResponsibility(),

            //
            "subscription_limitation_type"=> $ilias_course->getSubscriptionLimitationType(),
            "subscription_start" => $ilias_course->getSubscriptionStart(),
            "subscription_end" => $ilias_course->getSubscriptionEnd(),
            "subscription_type" => $ilias_course->getSubscriptionType(),

            "settings" => [

                ],
            "container_settings" => [
                "order_type" => $ilias_course->getOrderType(),
                "show_news" => $ilias_course->getNewsBlockActivated(),
                "news_timeline" => $ilias_course->getNewsTimeline()

            ]
        ];
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

