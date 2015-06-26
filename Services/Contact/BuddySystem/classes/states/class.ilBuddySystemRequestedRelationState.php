<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Contact/BuddySystem/classes/states/class.ilAbstractBuddySystemRelationState.php';

/**
 * Class ilBuddySystemRequestedRelationState
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilBuddySystemRequestedRelationState extends ilAbstractBuddySystemRelationState
{
	/**
	 *  {@inheritDoc}
	 */
	public function getName()
	{
		return 'Requested';
	}

	/**
	 *  {@inheritDoc}
	 */
	public function getAction()
	{
		return 'request';
	}

	/**
	 * @return ilBuddySystemRelationState[]
	 */
	public function getPossibleTargetStates()
	{
		return array(
			new ilBuddySystemUnlinkedRelationState(),
			new ilBuddySystemIgnoredRequestRelationState(),
			new ilBuddySystemLinkedRelationState()
		);
	}

	/**
	 * @param ilBuddySystemRelation
	 */
	public function unlink(ilBuddySystemRelation $relation)
	{
		$relation->setState(new ilBuddySystemUnlinkedRelationState());
	}

	/**
	 * @param ilBuddySystemRelation
	 */
	public function ignore(ilBuddySystemRelation $relation)
	{
		$relation->setState(new ilBuddySystemIgnoredRequestRelationState());
	}

	/**
	 * @param ilBuddySystemRelation
	 */
	public function link(ilBuddySystemRelation $relation)
	{
		$relation->setState(new ilBuddySystemLinkedRelationState());
	}
}