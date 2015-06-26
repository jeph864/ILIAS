<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Contact/BuddySystem/classes/states/class.ilAbstractBuddySystemRelationState.php';

/**
 * Class ilBuddySystemLinkedState
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilBuddySystemLinkedRelationState extends ilAbstractBuddySystemRelationState
{
	/**
	 *  {@inheritDoc}
	 */
	public function getName()
	{
		return 'Linked';
	}

	/**
	 *  {@inheritDoc}
	 */
	public function getAction()
	{
		return 'link';
	}

	/**
	 * @return ilBuddySystemRelationState[]
	 */
	public function getPossibleTargetStates()
	{
		return array(
			new ilBuddySystemUnlinkedRelationState()
		);
	}

	/**
	 * @param ilBuddySystemRelation
	 */
	public function unlink(ilBuddySystemRelation $relation)
	{
		$relation->setState(new ilBuddySystemUnlinkedRelationState());
	}
}