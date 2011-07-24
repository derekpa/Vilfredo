<?php
//******************************************/
// DOMAIN SPECIFIC SETTINGS
require_once "priv/config.domain.php";
require_once "priv/dbdata.php";
require_once "priv/sys.php";
require_once "priv/social.php";

require_once 'process_input.php';
require_once 'graphs.php';
require_once 'vga_functions.php';
include_once 'lib/php_lib.php';
require_once 'lib/htmlpurifier-4.0.0-live/HTMLPurifier.standalone.php';
require_once "lib/feedcreator-1.7.2-ppt/include/feedcreator.class.php";
require_once "vga_bubble_functions.php";

// Set error logs if log directory is defined (in config.domain.php)
if (defined('LOG_DIRECTORY'))
{
	ini_set('log_errors', 'On');
	ini_set('error_log', LOG_DIRECTORY.'vga_runtime_errors.log');

	define("ERROR_FILE", LOG_DIRECTORY."vga_error.log");
	define("LOG_FILE", LOG_DIRECTORY."vga.log");
}

ini_set('error_reporting', E_ALL & ~E_NOTICE);

// Start session for login and redirects
session_start();
// ******************************************
// Connects to the Database
mysql_connect($dbaddress, $dbusername, $dbpassword) or die(mysql_error());
mysql_set_charset('utf8');
mysql_select_db($dbname) or die(mysql_error());
//******************************************
//
// FACEBOOK CONNECT
//******************************************
require_once 'config.facebook.php';
require_once 'lib/facebook/php/facebook.php';
//require_once 'lib/facebook_v2/php/facebook.php';

$fb = new Facebook($facebook_key, $facebook_secret);

/*
	If $FACEBOOK_ID != NULL then current user is Facebook Authroized
*/
$FACEBOOK_ID = null;
if (USE_FACEBOOK_CONNECT)
{
	$FACEBOOK_ID = get_current_facebook_userid($fb);
}
//******************************************/
define("COOKIE_USER", "ID_my_site");
define("COOKIE_PASSWORD", "Key_my_site");
define("VGA_PL", "vgapl");
//
define("SHOW_QICON_ROOMS", TRUE);
//
// Query string parameters
define("QUERY_KEY_TODO", "todo");
define("QUERY_KEY_USER", "u");
define("QUERY_KEY_QUESTION", "q");
define("QUERY_KEY_ROOM", "room");
define("QUERY_KEY_PROPOSAL", "p");
define("QUERY_KEY_GENERATION", "g");
define("RANDOM_ROOM_CODE_LENGTH", 16);
//
define("USER_LOGIN_ID", 'vilfredo_user_id');
define("USER_LOGIN_MODE", 'vilfredo_login_mode');
//
define('ONE_YEAR', 3600*24*365);
define('TWO_DAYS', 3600*24*2);
define('ONE_HOUR', 3600);
define('FIVE_MINUTES', 300);

if (!defined('COOKIE_LIFETIME'))
{
	define('COOKIE_LIFETIME', TWO_DAYS);
}
//
// Get rid off this
#define("USE_PRIVACY_FILTER", TRUE);
//******************************************/
// TEMP WIN/PHP FIX
// Use a dummy function to return true if no checkdnsrr()
// --  This function not available on Windows platforms
//      before PHP version 5.3.0. For live windows platforms without
//	checkdnsrr() another function could be substituted.
//
//	Eg. From PHP Manual:  http://php.net/manual/en/function.checkdnsrr.php
//	For compatibility with Windows before this was implemented, 
//	then try the » PEAR class » Net_DNS. 
//	
//******************************************/
/*
function checkdnsrr($host, $type)
{
	return true;
}
*/
?>