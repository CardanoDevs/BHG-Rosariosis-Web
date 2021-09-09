<?php
/**
 * Jitsi Meet Rooms
 *
 * @package Jitsi Meet module
 */

require_once 'modules/Jitsi_Meet/includes/common.fnc.php';
require_once 'modules/Jitsi_Meet/includes/Rooms.fnc.php';

if ( User( 'PROFILE' ) === 'teacher' )
{
	$_ROSARIO['allow_edit'] = true;
}

// AJAX function: Send Invitation.
if ( $_REQUEST['modfunc'] === 'send_invite'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0
		&& filter_var( $_REQUEST['email'], FILTER_VALIDATE_EMAIL ) )
	{
		$room_RET = DBGet( "SELECT TITLE,SUBJECT,PASSWORD
			FROM JITSI_MEET_ROOMS
			WHERE ID='" . $_REQUEST['id'] . "'
			AND SYEAR='" . UserSyear() . "'" );

		if ( $room_RET )
		{
			$room = $room_RET[1];

			$url_link = JitsiMeetSiteURL() . 'Modules.php?modname=Jitsi_Meet/Meet.php&id=' . $_REQUEST['id'];

			if ( JitsiMeetSendInvitation( $_REQUEST['email'], $room['TITLE'], $room['SUBJECT'], $room['PASSWORD'], $url_link ) )
			{
				echo button( 'check' ) . ' ' . dgettext( 'Jitsi_Meet', 'Invitation Sent' );

				exit;
			}
		}
	}

	if ( $error )
	{
		echo ErrorMessage( $error );
	}

	exit;
}

DrawHeader( ProgramTitle() );

$_REQUEST['id'] = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = isset( $_REQUEST['table'])? $_REQUEST['table'] : '';

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ added SQL constraint TITLE is not null.
		if ( ! isset( $columns['TITLE'] )
			|| ! empty( $columns['TITLE'] ) )
		{
			// Update Field.
			if ( $id !== 'new' )
			{
				$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

				$go = true;
			}
			// New Field.
			else
			{
				$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

				$go = false;

				$fields = $values = '';

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value )
						|| $value == '0' )
					{
						$fields .= $column . ',';

						$values .= "'" . $value . "',";

						$go = true;
					}
				}

				$_REQUEST['id'] = DBSeqNextID( 'jitsi_meet_rooms_id_seq' );

				// Set Syear, Owner ID.
				$fields .= 'ID,SYEAR,OWNER_ID,';

				$values .= "'" . $_REQUEST['id'] . "','" . UserSyear() . "','" . User( 'STAFF_ID' ) . "',";

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
			}

			if ( $go )
			{
				DBQuery( $sql );
			}
		}
		else
			$error[] = _( 'Please fill in the required fields' );
	}

	// Unset tables & redirect URL.
	RedirectURL( 'tables' );
}

if ( $_REQUEST['modfunc'] === 'add'
	&& AllowEdit() )
{
	// Add Users or Students to Room.
	$room_RET = DBGet( "SELECT TITLE,USERS,STUDENTS
		FROM JITSI_MEET_ROOMS
		WHERE ID='" . $_REQUEST['id'] . "'
		AND SYEAR='" . UserSyear() . "'" );

	$room = $room_RET[1];

	$existing_user_ids = $_REQUEST['type'] === 'student' ? $room['STUDENTS'] : $room['USERS'];

	$existing_user_ids = trim( $existing_user_ids, ',' );

	$existing_user_ids = $existing_user_ids ? explode( ',', $existing_user_ids ) : array();

	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$column = $_REQUEST['type'] === 'user' ? DBEscapeIdentifier( 'USERS' ) : DBEscapeIdentifier( 'STUDENTS' );

		$user_ids = array_merge( $existing_user_ids, $_REQUEST['st_arr'] );

		$user_ids = ',' . implode( ',', $user_ids ) . ',';

		DBQuery( "UPDATE JITSI_MEET_ROOMS
			SET " . $column . "='" . $user_ids . "'
			WHERE ID='" . $_REQUEST['id'] . "'
			AND SYEAR='" . UserSyear() . "'" );

		// Unset modfunc & st_arr & type & redirect URL.
		RedirectURL( array( 'modfunc', 'st_arr', 'type' ) );
	}
	elseif ( User( 'PROFILE' ) === 'teacher'
		&& $_REQUEST['type'] === 'user_teacher_admin' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=add&id=' . $_REQUEST['id'] . '&type=user" method="POST">';

		DrawHeader( $room['TITLE'], SubmitButton( dgettext( 'Jitsi_Meet', 'Add Selected Users to Room' ) ) );

		// List Teachers and Administrators in user schools.
		JitsiMeetUserListOutputForTeachers( $existing_user_ids );

		echo '<br /><div class="center">' .
			SubmitButton( dgettext( 'Jitsi_Meet', 'Add Selected Users to Room' ) ) . '</div></form>';
	}
	else
	{
		// Search Users or Students.
		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=add&id=' . $_REQUEST['id'] . '&type=' . $_REQUEST['type'] . '" method="POST">';

			DrawHeader( $room['TITLE'], SubmitButton( dgettext( 'Jitsi_Meet', 'Add Selected Users to Room' ) ) );
		}
		else
		{
			DrawHeader( $room['TITLE'] );
		}

		$extra = JitsiMeetAddSearchExtra( $existing_user_ids, $_REQUEST['type'] );

		Search( ( $_REQUEST['type'] === 'user' ? 'staff_id' : 'student_id' ), $extra );

		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<br /><div class="center">' .
				SubmitButton( dgettext( 'Jitsi_Meet', 'Add Selected Users to Room' ) ) . '</div></form>';
		}
	}
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	// Remove User or Student from Room.
	$delete_title = $_REQUEST['type'] === 'user' ? _( 'User' ) : _( 'Student' );

	if ( intval( $_REQUEST['id'] ) > 0
		&& DeletePrompt( $delete_title, _( 'Remove' ) ) )
	{
		$column = $_REQUEST['type'] === 'user' ? DBEscapeIdentifier( 'USERS' ) : DBEscapeIdentifier( 'STUDENTS' );

		DBQuery( "UPDATE JITSI_MEET_ROOMS
			SET " . $column . "=REPLACE(" . $column . ",'," . $_REQUEST['user_id'] . ",',',')
			WHERE ID='" . $_REQUEST['id'] . "'
			AND SYEAR='" . UserSyear() . "'" );

		// Unset modfunc & user ID & type & redirect URL.
		RedirectURL( array( 'modfunc', 'user_id', 'type' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	// Delete Room.
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Jitsi_Meet', 'Room' ) ) )
		{
			DBQuery( "DELETE FROM JITSI_MEET_ROOMS
				WHERE ID='" . $_REQUEST['id'] . "'
				AND SYEAR='" . UserSyear() . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( array( 'modfunc', 'id' ) );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$RET = array();

	// ADDING & EDITING FORM.
	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,TITLE,SUBJECT,PASSWORD,START_AUDIO_ONLY,STUDENTS,USERS
		FROM JITSI_MEET_ROOMS
		WHERE ID='" . $_REQUEST['id'] . "'
		AND SYEAR='" . UserSyear() . "'" );

		$RET = $RET[1];
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$RET['ID'] = 'new';
	}

	echo JitsiMeetRoomsForm( $RET );

	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		if ( User( 'PROFILE' ) === 'teacher' )
		{
			// List Teachers and Admins added to Room (Parents are below).
			echo JitsiMeetRoomsListUsers( $RET, 'user_teacher_admin' );
		}

		// List Users and Students added to Room.
		echo JitsiMeetRoomsListUsers( $RET );

		echo JitsiMeetRoomsListUsers( $RET, 'student' );
	}

	// DISPLAY THE MENU.
	// ROOMS.
	$RET = DBGet( "SELECT ID,TITLE
	FROM JITSI_MEET_ROOMS
	WHERE OWNER_ID='" . User( 'STAFF_ID' ) . "'
	AND SYEAR='" . UserSyear() . "'" );

	echo '<div class="st">';

	JitsiMeetRoomsMenuOutput( $RET, $_REQUEST['id'] );

	echo '</div>';
}
