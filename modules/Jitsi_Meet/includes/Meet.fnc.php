<?php
/**
 * Meet functions
 *
 * @package Jitsi Meet module
 */

/**
 * Get logged in User (or Student) Rooms
 *
 * @return array $rooms_RET
 */
function JitsiMeetGetUserRooms()
{
	if ( User( 'STAFF_ID' ) )
	{
		$rooms_sql = "SELECT ID,TITLE,SUBJECT,CREATED_AT,
		(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE OWNER_ID=STAFF_ID) AS OWNER_NAME
		FROM JITSI_MEET_ROOMS
		WHERE OWNER_ID='" . User( 'STAFF_ID' ) . "'
		OR USERS LIKE '%," . User( 'STAFF_ID' ) . ",%'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY CREATED_AT DESC";
	}
	else
	{
		$rooms_sql = "SELECT ID,TITLE,SUBJECT,CREATED_AT,OWNER_ID,
		(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE OWNER_ID=STAFF_ID) AS OWNER_NAME
		FROM JITSI_MEET_ROOMS
		WHERE STUDENTS LIKE '%," . UserStudentID() . ",%'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY CREATED_AT DESC";
	}

	$rooms_RET = DBGet( $rooms_sql, array( 'CREATED_AT' => 'ProperDateTime' ) );

	return $rooms_RET;
}

/**
 * User Rooms Menu Output
 *
 * @uses ListOutput()
 *
 * @param array $RET User Rooms.
 */
function JitsiMeetUserRoomsMenuOutput( $RET )
{
	$LO_options = array( 'save' => false, 'search' => false, 'responsive' => false );

	$LO_columns = array(
		'TITLE' => dgettext( 'Jitsi_Meet', 'Room' ),
		'SUBJECT' => _( 'Description' ),
		'OWNER_NAME' => _( 'User' ),
		'CREATED_AT' => _( 'Date' ),
	);

	$LO_link = array();

	$LO_link['TITLE']['link'] = PreparePHP_SELF(
		array(),
		array( 'id' )
	);

	$LO_link['TITLE']['variables'] = array( 'id' => 'ID' );

	ListOutput(
		$RET,
		$LO_columns,
		dgettext( 'Jitsi_Meet', 'Room' ),
		dgettext( 'Jitsi_Meet', 'Rooms' ),
		$LO_link,
		array(),
		$LO_options
	);
}

/**
 * Check User has right to access the Room
 *
 * @param int $room_id Room ID.
 *
 * @return int Room ID or 0.
 */
function JitsiMeetCheckRoomID( $room_id )
{
	$room_RET = DBGet( "SELECT ID,TITLE,PASSWORD,START_AUDIO_ONLY,STUDENTS,USERS,OWNER_ID
	FROM JITSI_MEET_ROOMS
	WHERE ID='" . $room_id . "'
	AND SYEAR='" . UserSyear() . "'" );

	if ( ! $room_RET )
	{
		return 0;
	}

	$room = $room_RET[1];

	if ( User( 'STAFF_ID' )
		&& $room['OWNER_ID'] != User( 'STAFF_ID' )
		&& mb_strpos( $room['USERS'], ',' . User( 'STAFF_ID' ) . ',' ) === false )
	{
		return 0;
	}

	if ( ! User( 'STAFF_ID' )
		&& UserStudentID()
		&& mb_strpos( $room['STUDENTS'], ',' . UserStudentID() . ',' ) === false )
	{
		return 0;
	}

	return $room_id;
}

/**
 * Get Jitsi Room Parameters
 *
 * @uses JitsiMeetPhotoURL()
 * @uses Config( 'JITSI_MEET_*' )
 *
 * @param int $room_id Room ID.
 *
 * @return array Jitsi Room Parameters
 */
function JitsiMeetGetRoomParams( $room_id )
{
	global $locale;

	$room_RET = DBGet( "SELECT ID,TITLE,SUBJECT,PASSWORD,START_AUDIO_ONLY,STUDENTS,USERS
		FROM JITSI_MEET_ROOMS
		WHERE ID='" . $room_id . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( ! $room_RET )
	{
		return array();
	}

	$room = $room_RET[1];

	$language = mb_substr( $locale, 0, 2 );

	$photo_url = JitsiMeetPhotoURL();

	return array(
		'room' => $room['TITLE'],
		'domain' => Config( 'JITSI_MEET_DOMAIN' ),
		'width' => Config( 'JITSI_MEET_WIDTH' ),
		'height' => Config( 'JITSI_MEET_HEIGHT' ),
		'start_audio_only' => (bool) $room['START_AUDIO_ONLY'],
		'default_language' => $language,
		'brand_watermark_link' => Config( 'JITSI_MEET_BRAND_WATERMARK_LINK' ),
		'settings' => Config( 'JITSI_MEET_SETTINGS' ),
		'disable_video_quality_label' => (bool) Config( 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL' ),
		'toolbar' => Config( 'JITSI_MEET_TOOLBAR' ),
		'user' => User( 'NAME' ),
		'subject' => $room['SUBJECT'],
		'avatar' => $photo_url,
		'password' => $room['PASSWORD'],
		'is_admin' => User( 'STAFF_ID' )
	);
}

/**
 * Jitsi Room default Settings
 *
 * @return array Default settings.
 */
function JitsMeetDefaultSettings()
{
	return array(
		'enabled' => true,
		'room' => '',
		// 'domain' => 'meet.jit.si',
		'domain' => 'localhost:8000',
		'film_strip_only' => false,
		'width' => '100%',
		'height' => 700,
		'start_audio_only' => false,
		'parent_node' => '#meet',
		'default_language' => 'en',
		'background_color' => '#464646',
		'show_watermark' => true,
		'show_brand_watermark' => false,
		'brand_watermark_link' => '',
		'settings' => 'devices,language',
		'disable_video_quality_label' => false,
		'toolbar' => 'microphone,camera,hangup,desktop,fullscreen,profile,chat,recording,settings,raisehand,videoquality,tileview',
	);
}

/**
 * User or Student Photo URL
 *
 * @uses JitsiMeetSiteURL()
 *
 * @return string Empty or Photo URL.
 */
function JitsiMeetPhotoURL()
{
	global $UserPicturesPath,
		$StudentPicturesPath;

	if ( User( 'STAFF_ID' ) )
	{
		if ( ! file_exists( $picture_path = $UserPicturesPath . UserSyear() . '/' . User( 'STAFF_ID' ) . '.jpg' )
			&& ! file_exists( $picture_path = $UserPicturesPath . ( UserSyear() - 1 ) . '/' . User( 'STAFF_ID' ) . '.jpg' ) )
		{
			return '';
		}
	}
	else
	{
		if ( ! file_exists( $picture_path = $StudentPicturesPath . UserSyear() . '/' . UserStudentID() . '.jpg' )
			&& ! file_exists( $picture_path = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . UserStudentID() . '.jpg' ) )
		{
			return '';
		}
	}

	return JitsiMeetSiteURL() . $picture_path;
}

/**
 * Jitsi Meet Javascript & HTML code
 *
 * @uses JitsMeetDefaultSettings()
 * @uses JitsiMeetInitTemplate()
 *
 * @param array $params Room parameters.
 *
 * @return string Javascript & HTML code
 */
function JitsiMeetJSHTML( $params )
{
	if(User('PROFILE') == 'teacher' || User('PROFILE') == 'admin') {
		$params['lobby'] = "true";
	}
	else {
		$params['lobby'] = "false";
	}
	$params = array_replace_recursive( JitsMeetDefaultSettings(), $params );
	
	$script = sprintf(
		JitsiMeetInitTemplate(),
		$params['domain'],
		$params['settings'],
		$params['toolbar'],
		$params['room'],
		$params['width'],
		$params['height'],
		$params['parent_node'],
		$params['start_audio_only'] ? 1 : 0,
		$params['default_language'],
		$params['film_strip_only'] ? 1 : 0,
		$params['background_color'],
		$params['show_watermark'] && $params['domain'] === 'localhost:8000' ? 1 : 0, // show_watermark if on official domain.
		$params['brand_watermark_link'] ? 1 : 0, // show_brand_watermark if has link.
		$params['brand_watermark_link'],
		$params['disable_video_quality_label'] ? 1 : 0,
		isset( $params['user'] ) ? $params['user'] : '',
		$params['subject'],
		isset( $params['avatar'] ) ? $params['avatar'] : '',
		isset( $params['password'] ) ? $params['password'] : '',
		$params['is_admin'],
		$params['lobby'],
	);

	return '<div id="meet"></div>
		<script src="http://localhost:8000/external_api.js"></script>
		<script>function waitForJitsiMeet() {
			if (typeof JitsiMeetExternalAPI !== "undefined") {
				' . $script . '
			} else {
				console.log("Is it possible to use jitsi on my side")
				setTimeout(waitForJitsiMeet, 250);
			}
		}
		waitForJitsiMeet();</script>';
}

/**
 * Jitsi Meet Javascript Init template
 *
 * @link https://community.jitsi.org/t/setting-the-room-password-on-creation-using-the-jitsi-meet-api/19426/4
 * @link https://community.jitsi.org/t/lock-failed-on-jitsimeetexternalapi/32060/16
 *
 * @return string Javascript Init template
 */
function JitsiMeetInitTemplate()
{
	return 'const domain = "%1$s";
		const settings = "%2$s";
		const toolbar = "%3$s";
		const options = {
			roomName: "%4$s",
			width: "%5$s",
			height: %6$d,
			parentNode: document.querySelector("%7$s"),
			configOverwrite: {
				startAudioOnly: %8$b === 1,
				defaultLanguage: "%9$s",
			},
			interfaceConfigOverwrite: {
				filmStripOnly: %10$b === 1,
				DEFAULT_BACKGROUND: "%11$s",
				DEFAULT_REMOTE_DISPLAY_NAME: "",
				SHOW_JITSI_WATERMARK: %12$b === 1,
				SHOW_WATERMARK_FOR_GUESTS: %12$b === 1,
				SHOW_BRAND_WATERMARK: %13$b === 1,
				BRAND_WATERMARK_LINK: "%14$s",
				LANG_DETECTION: true,
				CONNECTION_INDICATOR_DISABLED: false,
				VIDEO_QUALITY_LABEL_DISABLED: %15$b === 1,
				SETTINGS_SECTIONS: settings.split(","),
				TOOLBAR_BUTTONS: toolbar.split(","),
			},
		};
		const api = new JitsiMeetExternalAPI(domain, options);
		api.executeCommand("displayName", "%16$s");
		api.addEventListeners({
			
				displayNameChange: function (params) {
					return this.executeCommand("displayName", "%20$s" === "0" ? "%16$s" : params.displayname);
				}
		});


		api.executeCommand("subject", "%17$s");
		api.executeCommand("avatarUrl", "%18$s");
		api.executeCommand("toggleLobby", %21$s);

		window.api = api;

		setTimeout(function(){
			api.addEventListener("videoConferenceJoined", function(event){
				setTimeout(function(){
					api.executeCommand("password", "%19$s");
				}, 300);
			});
			api.addEventListener("passwordRequired", function(event){
				api.executeCommand("password", "%19$s");
			});
		}, 10);';
}
