<?php
/**
 * Common functions
 *
 * @package Jisti Meet module
 */

/**
 * Site URL
 *
 * @return string Site URL.
 */
function JitsiMeetSiteURL()
{
	$page_url = 'http';

	if ( isset( $_SERVER['HTTPS'] )
		&& $_SERVER['HTTPS'] == 'on' )
	{
		$page_url .= 's';
	}

	$page_url .= '://';

	$root_pos = strpos( $_SERVER['REQUEST_URI'], 'Modules.php' ) ?
		strpos( $_SERVER['REQUEST_URI'], 'Modules.php' ) : strpos( $_SERVER['REQUEST_URI'], 'index.php' );

	$root_uri = substr( $_SERVER['REQUEST_URI'], 0, $root_pos );

	if ( $_SERVER['SERVER_PORT'] != '80'
		&& $_SERVER['SERVER_PORT'] != '443' )
	{
		$page_url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $root_uri;
	}
	else
	{
		$page_url .= $_SERVER['SERVER_NAME'] . $root_uri;
	}

	return $page_url;
}
