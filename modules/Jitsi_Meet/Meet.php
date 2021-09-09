<?php

/**
 * Meet program
 *
 * @package Jitsi Meet module
 */

require_once 'modules/Jitsi_Meet/includes/common.fnc.php';
require_once 'modules/Jitsi_Meet/includes/Meet.fnc.php';

DrawHeader( ProgramTitle() );

$room_id = JitsiMeetCheckRoomID( isset( $_REQUEST['id'])) ? $_REQUEST['id'] : ''; 
//echo $_REQUEST['id'];
if ( ! $_REQUEST['id'] )
{
	// List user rooms.
	$user_rooms = JitsiMeetGetUserRooms();

	JitsiMeetUserRoomsMenuOutput( $user_rooms );
}
else
{
	
	$params = JitsiMeetGetRoomParams( $_REQUEST['id']);

	echo JitsiMeetJSHTML( $params );
}

