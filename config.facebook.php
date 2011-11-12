<?php
//******************************************
//
// Code snippets to enable Facebook Connect 
//
//****************************************
function facebook_fbconnect_init_js($display=true) {
global $facebook_key, $fb;

$str = <<<_HTML_
<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
     <script type="text/javascript">
       FB.init("$facebook_key", "xd_receiver.htm");
       </script>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : ''; 
}

// Display Facebook Connect button
function facebook_connect_for_dialog($display=true) {
global $VGA_CONTENT;

$str = <<<_HTML_
<fb:login-button v="2" size="medium" onlogin="update_dialog();">{$VGA_CONTENT['fb_or_login_button']}</fb:login-button>

<script type="text/javascript">
function update_dialog() {
	$.event.trigger('fbuserauthorized');}
</script>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : '';
}

function facebook_login_button_refresh_2_plugin($display=true) 
{
global $FACEBOOK_ID;
global $VGA_CONTENT;

if ($FACEBOOK_ID != null && ($userid = fb_isconnected($FACEBOOK_ID)))
{
	$goto = 'plugin_fb_login.php';
	$button_txt = $VGA_CONTENT['fb_login_button'];
}
else
{
	$goto = 'plugin_fb_register.php';
	$button_txt = $button_txt = $VGA_CONTENT['fb_connect_button'];
}

$str = <<<_HTML_
{$VGA_CONTENT['or_use_fb_label']} <br/><br/>

<fb:login-button v="2" size="medium" onlogin="refresh_page();">$button_txt</fb:login-button>

<script type="text/javascript">
function refresh_page() {
	  window.location = "$goto";}
</script>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : ''; 
}

function facebook_login_button_refresh_2($display=true) 
{

global $FACEBOOK_ID;
global $VGA_CONTENT;

if ($FACEBOOK_ID != null && ($userid = fb_isconnected($FACEBOOK_ID)))
{
	$goto = 'fb_login.php';
	$button_txt = $VGA_CONTENT['fb_login_button'];
}
else
{
	$goto = 'fb_register.php';
	$button_txt = $button_txt = $VGA_CONTENT['fb_connect_button'];
}

$str = <<<_HTML_
{$VGA_CONTENT['or_use_fb_label']} <br/><br/>

<fb:login-button v="2" size="medium" onlogin="refresh_page();">$button_txt</fb:login-button>

<script type="text/javascript">
function refresh_page() {
	  window.location = "$goto";}
</script>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : ''; 
}

function facebook_login_button_refresh($goto, $display=true) {
$str = <<<_HTML_
Or <b>login</b> with Facebook:<br/><br/>

<fb:login-button v="2" size="medium" onlogin="refresh_page();">Connect with Facebook</fb:login-button>

<script type="text/javascript">
function refresh_page() {
	  window.location = "$goto";}
</script>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : ''; 
}

// Display Profile Pic
function facebook_profile_pic($display=true) {
$str = <<<_HTML_
<fb:profile-pic uid="loggedinuser" size="square" facebook-logo="true"></fb:profile-pic>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : '';
}

// Display Username
function facebook_username($display=true) {
$str = <<<_HTML_
<fb:name uid="loggedinuser" useyou="false" linked="false"></fb:name>
_HTML_;
return (USE_FACEBOOK_CONNECT && $display) ? $str : '';
}