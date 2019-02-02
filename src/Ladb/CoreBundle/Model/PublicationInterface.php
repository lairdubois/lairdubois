<?php

namespace Ladb\CoreBundle\Model;

interface PublicationInterface extends IdentifiableInterface, TypableInterface, TimestampableInterface {

	const NOTIFICATION_STRATEGY_NONE 		= 0;
	const NOTIFICATION_STRATEGY_FOLLOWER 	= 1;	// 0x01
	const NOTIFICATION_STRATEGY_WATCH 		= 2;	// 0x10

	// NotificationStrategy /////

	public function getNotificationStrategy();

}
