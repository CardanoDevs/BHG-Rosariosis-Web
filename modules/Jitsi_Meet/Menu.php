<?php
/**
 * Menu.php file
 * Required
 * - Menu entries for the Jitsi_Meet module
 *
 * @package Jitsi_Meet module
 */

// Menu entries for the Jitsi_Meet module.
$menu['Jitsi_Meet']['admin'] = array( // Admin menu.
	'title' => dgettext( 'Jitsi_Meet', 'Jitsi Meet' ),
	'default' => 'Jitsi_Meet/Rooms.php', // Program loaded by default when menu opened.
	'Jitsi_Meet/Meet.php' => dgettext( 'Jitsi_Meet', 'Meet' ),
	1 => _( 'Setup' ),
	'Jitsi_Meet/Rooms.php' => dgettext( 'Jitsi_Meet', 'My Rooms' ),
	'Jitsi_Meet/Configuration.php' => _( 'Configuration' ),
);

$menu['Jitsi_Meet']['teacher'] = array( // Teacher menu
	'title' => dgettext( 'Jitsi_Meet', 'Jitsi Meet' ),
	'default' => 'Jitsi_Meet/Rooms.php', // Program loaded by default when menu opened.
	'Jitsi_Meet/Meet.php' => dgettext( 'Jitsi_Meet', 'Meet' ),
	1 => _( 'Setup' ),
	'Jitsi_Meet/Rooms.php' => dgettext( 'Jitsi_Meet', 'My Rooms' ),
);

$menu['Jitsi_Meet']['parent'] = array( // Parent menu
	'title' => dgettext( 'Jitsi_Meet', 'Jitsi Meet' ),
	'default' => 'Jitsi_Meet/Meet.php', // Program loaded by default when menu opened.
	'Jitsi_Meet/Meet.php' => dgettext( 'Jitsi_Meet', 'Meet' ),
);
