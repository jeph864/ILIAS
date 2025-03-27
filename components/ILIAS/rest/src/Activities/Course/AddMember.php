<?php
declare(strict_types=1);

namespace ILIAS\Course\Activities;

use ilCourseParticipants;
use ILIAS\Component\Activities\ObjectActivity;
use ILIAS\Data\Description\Description;
use ILIAS\Data\Result;
use ILIAS\Export\ImportStatus\Exception\ilException;
use ILIAS\UI\Component\Input\Input;
use ilObjCourse;
use ILIAS\Data\Factory;
use ilObject;
use ilObjectFactory;
use ilParticipants;
use function PHPUnit\Framework\isTrue;


class AddMember extends \ILIAS\Component\Activities\Command implements ObjectActivity
{

    public function __construct(
        protected $data_factory = new \ILIAS\Data\Factory()
    )
    {

    }

    public function getName(): \ILIAS\Component\Dependencies\Name
    {
        return new \ILIAS\Component\Dependencies\Name(self::class);
    }


    public function getDescription(): \ILIAS\Data\Text\SimpleDocumentMarkdown
    {
        return $this->data_factory->text()->markdown()->simpleDocument('Adds a member to course');
    }


    public function isAllowedToPerform(int $usr_id, mixed $parameters): bool
    {
        global $DIC;
        return $DIC->rbac()->system()->checkAccess('manage_members', $parameters['id']);
    }

    public function perform(mixed $parameters): mixed
    {
        $course_id = $parameters['id'];
        $type = $parameters['type'];
        $user_id = $parameters['user_id'];
        $is_contact = $parameters['is_contact'];
        if (ilObject::_lookupType(ilObject::_lookupObjId($course_id)) !== 'crs') {
            $ref_ids = ilObject::_getAllReferences($course_id);
            $course_id = end($ref_ids);
            if (ilObject::_lookupType(ilObject::_lookupObjId($course_id)) !== 'crs') {
                throw new ilException('Invalid course id. Object with id "' . $course_id . '" is not of type "course"',
                    );
            }
        }
        if (ilObject::_lookupType($user_id) !== 'usr') {
            throw new ilException('Invalid user id. User with id "' . $user_id . ' does not exist');
        }
        $valid_roles = [
            'Admin' => ilParticipants::IL_CRS_ADMIN,
            'Tutor' => ilParticipants::IL_CRS_TUTOR,
            'Member' => ilParticipants::IL_CRS_MEMBER
        ];
        if (!isset($valid_roles[$type])) {
            $this->raiseError('Invalid type. Must be "Admin", "Tutor", or "Member"', 'Client');
        }

        if (!$tmp_course = ilObjectFactory::getInstanceByRefId($course_id, false)) {
            $this->raiseError('Cannot create course instance!', 'Server');
        }

        if (!$tmp_user = ilObjectFactory::getInstanceByObjId($user_id, false)) {
            $this->raiseError('Cannot create user instance!', 'Server');
        }

        $course_members = ilCourseParticipants::_getInstanceByObjId($tmp_course->getId());

        $course_members->add($tmp_user->getId(), $valid_roles[$type]);

        if($type === 'Admin' || $type === 'Tutor') {

            if ($is_contact) {
                $course_members->updateContact($tmp_user->getId(), $is_contact);
            }
        }
        return array(
            'status' => true,
            'is_contact' => $course_members->isContact($user_id),
            'role' => (
                $course_members->isAdmin($user_id) ?
                    'admin': ($course_members->isTutor($user_id)?
                    'tutor':
                    'member')
            )
        );

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

    public function getInputDescription(): \ILIAS\UI\Component\Input\Input
    {
        global $DIC;
        $ui_factory = $DIC->ui()->factory();
        $refinery = $DIC->refinery();

        $inputs = [
            'id' => $ui_factory->input()->field()->numeric('Course Object ID')
                ->withAdditionalTransformation($refinery->int()->isGreaterThan(0))
                ->withDedicatedName('id'),
            'user_id' => $ui_factory->input()->field()->numeric('User Id')
            ->withAdditionalTransformation($refinery->int()->isGreaterThan(0))
                ->withAdditionalTransformation($refinery->int()->isLessThan(1000))
                ->withRequired(true)
            ->withDedicatedName('user_id'),
            'type' => $ui_factory->input()->field()->text('Member role')
                ->withDedicatedName('type')
            ->withAdditionalTransformation($refinery->custom()->constraint(function (string $v) {
                return in_array(strtolower($v), ['admin', 'tutor', 'member']);
            }, 'Invalid membership Type.'
            )),
            'is_contact' => $ui_factory->input()->field()->numeric('Is Contact person')
                ->withDedicatedName('is_contact')
            ->withAdditionalTransformation($refinery->logical()->parallel([
                $refinery->int()->isGreaterThanOrEqual(0),
                $refinery->int()->isGreaterThanOrEqual(1)
            ])),

        ];
        return $ui_factory->input()->field()->group( $inputs)->withDedicatedName('inputs')
            ;
    }

    public function getOutputDescription(\ILIAS\Data\Description\Factory $f): Description
    {
        return $f->object(
            "Status",
            $f->field("status", $f->bool("Status of registration")),
            $f->field("role", $f->string("Membership")),
            $f->field("membership", $f->string("Membership"))

        );
    }

    public function getCheckedInputDescription(): Input
    {
        global $DIC;
        $ui_factory = $DIC->ui()->factory();
        return $ui_factory->input()->field()->json([]);

    }

    public function getTargetType(): string
    {
        return 'crs';
    }

    private function raiseError(string $error) :void
    {
        throw new ilException($error);
    }
}

