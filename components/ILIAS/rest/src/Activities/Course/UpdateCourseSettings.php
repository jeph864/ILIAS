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
use ILIAS\Component\Activities\ObjectActivity;
use ILIAS\Component\Activities\Query;
use ILIAS\Data\Result;
use ILIAS\Data\Text\SimpleDocumentMarkdown;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ilObjCourse;
use ILIAS\Data\Factory;
use ilObjectFactory;
use ilObjectNotFoundException;

/**
 *
 */
class UpdateCourseSettings extends Command implements ObjectActivity
{
    use AbstractCourseActivity;
    private Factory $factory;
    public function __construct()
    {
        $this->factory = new Factory();
    }
    public function getName(): \ILIAS\Component\Dependencies\Name
    {
        return new \ILIAS\Component\Dependencies\Name(self::class);
    }


    public function getDescription(): SimpleDocumentMarkdown
    {
        return  (new Factory())->text()->markdown()->simpleDocument("Updates course settings");
    }


    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        return true;
    }


    public function perform(mixed $parameters): mixed
    {
        /** @var ilObjCourse $ilias_course */
        $ilias_course =  ilObjectFactory::getInstanceByRefId($parameters['ref_id']);

        foreach ($parameters as $parameter => $value) {
            if ($value == null){
                continue;
            }
            switch ($parameter) {
                case 'title':
                    $ilias_course->setTitle($value);
                    break;
                case 'parent_id':
                    break;
                case 'description':
                    $ilias_course->setDescription($value);
                    break;
                case 'contact_consultation':
                    $ilias_course->setContactConsultation($value);
                    break;
                case 'contact_name':
                    $ilias_course->setContactName($value);
                    break;
                case 'contact_email':
                    $ilias_course->setContactEmail($value);
                    break;
                case 'subscription_limitation_type':
                    $ilias_course->setSubscriptionLimitationType($value);
                    break;
                case 'subscription_start':
                    $ilias_course->setSubscriptionStart($value);;
                    break;
                case 'subscription_end':
                    $ilias_course->setSubscriptionEnd($value);
                    break;
                case 'subscription_type':
                    $ilias_course->setSubscriptionType($value);
                    break;
                case 'syllabus':
                    $ilias_course->setSyllabus($value);
                    break;
                case 'container_settings':
                    foreach ($value as $key => $setting){
                        switch ($key){
                            case 'news_timeline':
                                $ilias_course->setNewsTimeline($setting);
                                break;
                            case 'news_timeline_auto_entries':
                                $ilias_course->setNewsTimelineAutoEntries($setting);
                                break;
                            case 'news_timeline_landing_page':
                                $ilias_course->setNewsTimelineLandingPage($setting);
                                break;
                            default:
                        }
                    }
                default:
                    //

            }
        }
        $ilias_course->update();
        return $this->factory->ok($ilias_course->getRefId());

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

