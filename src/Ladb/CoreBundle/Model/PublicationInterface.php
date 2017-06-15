<?php

namespace Ladb\CoreBundle\Model;

interface PublicationInterface extends IdentifiableInterface, TypableInterface, DraftableInterface {

	const NOTIFICATION_STRATEGY_NONE 		= 0;
	const NOTIFICATION_STRATEGY_FOLLOWER 	= 1;	// 0x01
	const NOTIFICATION_STRATEGY_WATCH 		= 2;	// 0x10

	// CreatedAt /////

	public function setCreatedAt($createdAt);

	public function getCreatedAt();

	// UpdatedAt /////

	public function setUpdatedAt($updatedAt);

	public function getUpdatedAt();

	// UpdatedAge /////

	public function getUpdatedAge();

	// ChangedAt /////

	public function setChangedAt($changedAt);

	public function getChangedAt();

	// NotificationStrategy /////

	public function getNotificationStrategy();

}
