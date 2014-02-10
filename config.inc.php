<?php
// Start session for login and redirects
session_start();
//*******************
if (!is_dir("logs"))
{
	mkdir("logs", 0755);
}
if (!is_dir("rss"))
{
	mkdir("rss", 0755);
}
if (!is_dir("map"))
{
	mkdir("map", 0755);
}

/*
$counter++;
if ($counter > 1)
{
	throw new Exception('config file included more than once!!!');
}
*/

//******************************************/
// DOMAIN SPECIFIC SETTINGS
//******************************************/

#define("TEST_LIVE", TRUE); //test the live site

// Look above document root then in current directory for priv.php
if ($priv = realpath($_SERVER["DOCUMENT_ROOT"] . "/priv_local/"))
{
	define("PRIV", $priv);
}
elseif ($privfile = realpath($_SERVER["DOCUMENT_ROOT"] . "/../priv.php"))
{
	include $privfile;
}
elseif ($privfile = realpath($_SERVER["DOCUMENT_ROOT"] . "/priv.php"))
{
	include $privfile;
}
// else use the default priv directory
else
{
	define("PRIV", "priv");
}

require_once PRIV."/config.domain.php";
require_once PRIV."/dbdata.php";
require_once PRIV."/social.php";

require_once 'lib/phpass-0.3/PasswordHash.php';
require_once "sys.php";
require_once 'process_input.php';
require_once 'graphs.php';
require_once 'vga_functions.php';
include_once 'lib/php_lib.php';
//require_once 'lib/htmlpurifier-4.0.0-live/HTMLPurifier.standalone.php';
require_once "lib/feedcreator-1.7.2-ppt/include/feedcreator.class.php";
//require_once "vga_bubble_functions.php";

// Set error logs if log directory is defined (in config.domain.php)
if (defined('LOG_DIRECTORY'))
{
	ini_set('log_errors', 'On');
	ini_set('error_log', LOG_DIRECTORY.'vga_runtime_errors.log');

	define("ERROR_FILE", LOG_DIRECTORY."vga_error.log");
	define("LOG_FILE", LOG_DIRECTORY."vga.log");
	define("BAD_INPUT_LOG", LOG_DIRECTORY."bad_input.log");
}

define("SVG_DIR", "svg-1.4.4");

define("MAX_LEN_EMAIL", 60);
define("MAX_LEN_USERNAME", 50);
define("MAX_LEN_PASSWORD", 60);
define("MIN_LEN_PASSWORD", 6);
define("MAX_LEN_ROOM", 20);
define("MIN_LEN_ROOM", 2);
define("MAX_LEN_PROPOSAL_ABSTRACT", 1000);
define("MAX_LEN_PROPOSAL_BLURB", 1000);

define("LANG_FILES_DIRECTORY", 'generatedlangfiles');

define("NOT_VOTED", 0);
define("AGREE", 1);
define("DISAGREE", 2);
define("NOT_UNDERSTAND", 3);

define('USE_CAPTCHA', false);

define("SNAPSHOTS_PATH", "snapshots/");
if (!is_dir(SNAPSHOTS_PATH))
{
	if (!mkdir(SNAPSHOTS_PATH, 0755, true))
	{
		log_error("Failed to create snapshots directory path");
	}
}


// Admin display settings
$display_interactive_graphs = True;
$display_key_players = True;
$display_confused_voting_option = True;
$anonymize_graph = True;

$default_voting_options = array (
	'display_interactive_graphs' => 1,
	'display_key_players' => 1,
	'display_confused_voting_option' => 1,
	'use_voting_comments' => 'No',
	'anonymize_graph' => 1,
	'personalize_graph' => 1,
	'proposal_node_layout' => 'Layers',
	'user_node_layout' => 'Layers',
	'pareto_proposal_node_layout' => 'Layers',
	'pareto_user_node_layout' => 'Layers',
	'display_all_previous_comments' => 0
);

$voting_settings = array_merge($default_voting_options, fetch_voting_settings());

//set_log("Counter = $counter");

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
require_once 'lib/facebook_v3/src/facebook.php';

// V3
$fb = new Facebook(array(
  'appId'  => $facebook_key,
  'secret' => $facebook_secret,
  'cookie' => true
));
/*
	If $FACEBOOK_ID != NULL then current user is Facebook Authroized
*/
$FACEBOOK_ID = null;
$FACEBOOK_USER_PROFILE = null;


if (USE_FACEBOOK_CONNECT)
{
	// Get User ID
	$FACEBOOK_ID = $fb->getUser();
	
	// Get user profile - if session valid
	if ($FACEBOOK_ID) 
	{
		try 
		{
			$FACEBOOK_USER_PROFILE = $fb->api('/'.$FACEBOOK_ID);
		} 
		catch (FacebookApiException $e) 
		{
			//set_log("FB Profile locale not set");
			set_log("Error fetching user profile: ".$e);
			$FACEBOOK_ID = null;
		}
	}
	else
	{
		//set_log('$FACEBOOK_ID not set');
	}
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
define("QUERY_KEY_QUESTION_BUBBLE", "qb");
define("COOKIE_KEY_QUESTION_BUBBLE", "qb");
define("RANDOM_ROOM_CODE_LENGTH", 16);
//
define("USER_LOGIN_ID", 'vilfredo_user_id');
define("USER_LOGIN_MODE", 'vilfredo_login_mode');
//
if (!defined('PWD_RESET_LIFETIME'))
{
	define('PWD_RESET_LIFETIME', 3600*24*2);
}
if (!defined('CHECK_EMAIL_LIFETIME'))
{
	define('CHECK_EMAIL_LIFETIME', 3600*24*2);
}
if (!defined('COOKIE_LIFETIME'))
{
	define('COOKIE_LIFETIME', 3600*24*2);
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