<?php
/**
 * Jitsi Meet Configuration
 *
 * @package Jitsi Meet module
 */

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values']['CONFIG'] )
		&& $_POST['values']
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values']['CONFIG'] as $column => $value )
		{
			// Update CONFIG value.
			Config( $column, $value );
		}

		$note[] = button( 'check' ) . '&nbsp;' . dgettext( 'Jitsi_Meet', 'The module configuration has been modified.' );
	}

	// Unset modfunc & values & redirect URL.
	RedirectURL( 'modfunc', 'values' );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton() );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable( 'header', _( 'Configuration' ) );

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The domain the Jitsi Meet server runs. Defaults to their free hosted service.' ) . '</i></div>';

	echo '<table class="width-100p"><tr><td>' . TextInput(
		Config( 'JITSI_MEET_DOMAIN' ),
		'values[CONFIG][JITSI_MEET_DOMAIN]',
		dgettext( 'Jitsi_Meet', 'Domain' ) . $tooltip,
		'required'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The toolbar buttons to display in comma separated format. For more information refer to <a target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L49">TOOLBAR_BUTTONS</a>.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_TOOLBAR' ),
		'values[CONFIG][JITSI_MEET_TOOLBAR]',
		dgettext( 'Jitsi_Meet', 'Toolbar' ) . $tooltip,
		'size="30"'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The settings available in comma separated format. For more information refer to <a target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L58">SETTINGS_SECTION</a>.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_SETTINGS' ),
		'values[CONFIG][JITSI_MEET_SETTINGS]',
		dgettext( 'Jitsi_Meet', 'Settings' ) . $tooltip
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The width in pixels or percentage of the embedded window.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_WIDTH' ),
		'values[CONFIG][JITSI_MEET_WIDTH]',
		dgettext( 'Jitsi_Meet', 'Width' ) . $tooltip,
		'required'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The height in pixels or percentage of the embedded window.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_HEIGHT' ),
		'values[CONFIG][JITSI_MEET_HEIGHT]',
		dgettext( 'Jitsi_Meet', 'Height' ) . $tooltip,
		'required'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'The link for the brand watermark.' ) . '</i></div>';

	echo '<tr><td>' . TextInput(
		Config( 'JITSI_MEET_BRAND_WATERMARK_LINK' ),
		'values[CONFIG][JITSI_MEET_BRAND_WATERMARK_LINK]',
		dgettext( 'Jitsi_Meet', 'Brand Watermark Link' ) . $tooltip,
		'size="30"'
	) . '</td></tr>';

	$tooltip = ' <div class="tooltip"><i>' .
		dgettext( 'Jitsi_Meet', 'Hide/Show the video quality indicator.' ) . '</i></div>';

	echo '<tr><td>' . CheckboxInput(
		Config( 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL' ),
		'values[CONFIG][JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL]',
		dgettext( 'Jitsi_Meet', 'Disable Video Quality Indicator' ) . $tooltip
	) . '</td></tr>';

	echo '</table>';

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';

	echo '</form>';
}
