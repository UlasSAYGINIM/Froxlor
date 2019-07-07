<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org> (2003-2009)
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    System
 *
 */

// define default theme for configurehint, etc.
$_deftheme = 'Sparkle';

if (! file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
	// get hint-template
	$vendor_hint = file_get_contents(dirname(__DIR__) . '/templates/' . $_deftheme . '/misc/vendormissinghint.tpl');
	// replace values
	$vendor_hint = str_replace("<FROXLOR_INSTALL_DIR>", dirname(__DIR__), $vendor_hint);
	$vendor_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $vendor_hint);
	die($vendor_hint);
}

require dirname(__DIR__) . '/vendor/autoload.php';

session_start();

use Froxlor\Database\Database;
use Froxlor\Settings;

// check whether the userdata file exists
if (! file_exists(\Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php')) {
	$config_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/configurehint.tpl');
	$config_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $config_hint);
	die($config_hint);
}

// check whether we can read the userdata file
if (! is_readable(\Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php')) {
	// get possible owner
	$posixusername = posix_getpwuid(posix_getuid());
	$posixgroup = posix_getgrgid(posix_getgid());
	// get hint-template
	$owner_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/ownershiphint.tpl');
	// replace values
	$owner_hint = str_replace("<USER>", $posixusername['name'], $owner_hint);
	$owner_hint = str_replace("<GROUP>", $posixgroup['name'], $owner_hint);
	$owner_hint = str_replace("<FROXLOR_INSTALL_DIR>", \Froxlor\Froxlor::getInstallDir(), $owner_hint);
	$owner_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $owner_hint);
	// show
	die($owner_hint);
}

// includes MySQL-Username/Passwort etc.
require \Froxlor\Froxlor::getInstallDir() . '/lib/userdata.inc.php';

if (! isset($sql) || ! is_array($sql)) {
	$config_hint = file_get_contents(\Froxlor\Froxlor::getInstallDir() . '/templates/' . $_deftheme . '/misc/configurehint.tpl');
	$config_hint = str_replace("<CURRENT_YEAR>", date('Y', time()), $config_hint);
	die($config_hint);
}

// register error-handler
@set_error_handler(array(
	'\\Froxlor\\PhpHelper',
	'phpErrHandler'
));

// includes the MySQL-Tabledefinitions etc.
require \Froxlor\Froxlor::getInstallDir() . '/lib/tables.inc.php';

// send headers
\Froxlor\Frontend\UI::sendHeaders();

// init template engine
\Froxlor\Frontend\UI::initTwig();
\Froxlor\Frontend\UI::Twig()->addGlobal('global_errors', '');

// re-read user data if logged in
if (\Froxlor\CurrentUser::hasSession()) {
	\Froxlor\CurrentUser::reReadUserData();
}

// Language Managament
$langs = array();
$languages = array();
$iso = array();

// query the whole table
$result_stmt = Database::query("SELECT * FROM `" . TABLE_PANEL_LANGUAGE . "`");

// presort languages
while ($row = $result_stmt->fetch(PDO::FETCH_ASSOC)) {
	$langs[$row['language']][] = $row;
	// check for row[iso] cause older froxlor
	// versions didn't have that and it will
	// lead to a lot of undfined variables
	// before the admin can even update
	if (isset($row['iso'])) {
		$iso[$row['iso']] = $row['language'];
	}
}

// buildup $languages for the login screen
foreach ($langs as $key => $value) {
	$languages[$key] = $key;
}

// set default language before anything else to
// ensure that we can display messages
$language = Settings::Get('panel.standardlanguage');

if (\Froxlor\CurrentUser::hasSession() && !empty(\Froxlor\CurrentUser::getField('language')) && isset($languages[\Froxlor\CurrentUser::getField('language')])) {
	// default: use language from session, #277
	$language = \Froxlor\CurrentUser::getField('language');
} else {
	if (!\Froxlor\CurrentUser::hasSession()) {
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$accept_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			for ($i = 0; $i < count($accept_langs); $i ++) {
				// this only works for most common languages. some (uncommon) languages have a 3 letter iso-code.
				// to be able to use these also, we would have to depend on the intl extension for php (using Locale::lookup or similar)
				// as long as froxlor does not support any of these languages, we can leave it like that.
				if (isset($iso[substr($accept_langs[$i], 0, 2)])) {
					$language = $iso[substr($accept_langs[$i], 0, 2)];
					break;
				}
			}
			unset($iso);

			// if HTTP_ACCEPT_LANGUAGES has no valid langs, use default (very unlikely)
			if (! strlen($language) > 0) {
				$language = Settings::Get('panel.standardlanguage');
			}
		}
	} else {
		$language = \Froxlor\CurrentUser::getField('def_language');
	}
}

// include every english language file we can get
foreach ($langs['English'] as $key => $value) {
	include_once \Froxlor\FileDir::makeSecurePath(\Froxlor\Froxlor::getInstallDir() . '/' . $value['file']);
}

// now include the selected language if its not english
if ($language != 'English') {
	foreach ($langs[$language] as $key => $value) {
		include_once \Froxlor\FileDir::makeSecurePath(\Froxlor\Froxlor::getInstallDir() . '/' . $value['file']);
	}
}

// last but not least include language references file
include_once \Froxlor\FileDir::makeSecurePath(\Froxlor\Froxlor::getInstallDir() . '/lng/lng_references.php');

// set language for Frontend
\Froxlor\Frontend\UI::setLng($lng);

// check for custom header-graphic
$hl_path = '../templates/Sparkle2/assets/img';
$header_logo = $hl_path . '/logo.svg';

if (file_exists($hl_path . '/logo_custom.png')) {
	$header_logo = $hl_path . '/logo_custom.png';
}
\Froxlor\Frontend\UI::Twig()->addGlobal('header_logo', $header_logo);

// get module and view
$module = isset($_GET['module']) ? $_GET['module'] : 'login';
$view = isset($_GET['view']) ? $_GET['view'] : 'overview';

if (\Froxlor\CurrentUser::hasSession() == false && strtolower($module) != 'login') {
	$qrystr = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
	header("Location: index.php?module=login&qrystr=".$qrystr);
	exit();
} elseif (\Froxlor\CurrentUser::hasSession() && (strtolower($module) == 'login' && $view != 'su')) {
	$module = "Index";
	if (\Froxlor\CurrentUser::isAdmin()) {
		$module = 'admin' . $module;
	} else {
		$module = 'customer' . $module;
	}
	header("Location: index.php?module=" . $module);
	exit();
}

// navigation
if (\Froxlor\CurrentUser::hasSession()) {
	// Fills variables for navigation, header and footer
	$navigation = "";
	if (\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) {
		// if froxlor-files have been updated
		// but not yet configured by the admin
		// we only show logout and the update-page
		$navigation_data = array(
			'admin' => array(
				'index' => array(
					'url' => 'index.php?module=AdminIndex',
					'label' => $lng['admin']['overview'],
					'elements' => array(
						array(
							'label' => $lng['menue']['main']['username']
						),
						array(
							'url' => 'index.php?module=AdminIndex&view=logout',
							'label' => $lng['login']['logout']
						)
					)
				),
				'server' => array(
					'label' => $lng['admin']['server'],
					'required_resources' => 'change_serversettings',
					'elements' => array(
						array(
							'url' => 'index.php?module=AdminUpdates',
							'label' => $lng['update']['update'],
							'required_resources' => 'change_serversettings'
						)
					)
				)
			)
		);
		$navigation = $navigation_data['admin'];
	} else {
		$navigation_data = \Froxlor\PhpHelper::loadConfigArrayDir(\Froxlor\Froxlor::getInstallDir() . '/lib/navigation/');
		$area = \Froxlor\CurrentUser::isAdmin() ? 'admin' : 'customer';
		$navigation = $navigation_data[$area];
	}
	unset($navigation_data);
	\Froxlor\Frontend\UI::Twig()->addGlobal('nav_items', $navigation);
}

$no_log_modules = array(
	'Login',
	'NewsFeed'
);

$module = ucfirst($module);
\Froxlor\Frontend\UI::Twig()->addGlobal('module', $module);
\Froxlor\Frontend\UI::Twig()->addGlobal('view', $view);
$mod_fullpath = '\\Froxlor\\Frontend\\Modules\\' . $module;

if (! class_exists($mod_fullpath)) {
	\Froxlor\UI\Response::dynamic_error(sprintf(_('Module %s does not exist'), $module));
} else {
	$mod = new $mod_fullpath();
	if (method_exists($mod_fullpath, $view)) {
		$mod->lng = $lng;
		$mod->mail = new \Froxlor\System\Mailer(true);
		if (\Froxlor\CurrentUser::hasSession() && !in_array($module, $no_log_modules)) {
			\Froxlor\FroxlorLogger::getLog()->addNotice("viewed " . $module . "::" . $view);
		}
		$mod->{$view}();
	} else {
		\Froxlor\UI\Response::dynamic_error(sprintf(_('Module function %s does not exist'), $view));
	}
}
/*
$timediff = time() - Settings::Get('session.sessiontimeout');
$del_stmt = Database::prepare("
	DELETE FROM `" . TABLE_PANEL_SESSIONS . "` WHERE `lastactivity` < :timediff
");
Database::pexecute($del_stmt, array(
	'timediff' => $timediff
));

$userinfo = array();

if (isset($s) && $s != "" && $nosession != 1) {
	ini_set("session.name", "s");
	ini_set("url_rewriter.tags", "");
	ini_set("session.use_cookies", false);
	session_id($s);
	session_start();

	if (\Froxlor\CurrentUser::getField('adminsession') == 1) {
		define('AREA', 'admin');
	} else {
		define('AREA', 'customer');
	}

	$query = "SELECT `s`.*, `u`.* FROM `" . TABLE_PANEL_SESSIONS . "` `s` LEFT JOIN `";

	if (AREA == 'admin') {
		$query .= TABLE_PANEL_ADMINS . "` `u` ON (`s`.`userid` = `u`.`adminid`)";
		$adminsession = '1';
	} else {
		$query .= TABLE_PANEL_CUSTOMERS . "` `u` ON (`s`.`userid` = `u`.`customerid`)";
		$adminsession = '0';
	}

	$query .= " WHERE `s`.`hash` = :hash AND `s`.`ipaddress` = :ipaddr
		AND `s`.`useragent` = :ua AND `s`.`lastactivity` > :timediff
		AND `s`.`adminsession` = :adminsession
	";

	$userinfo_data = array(
		'hash' => $s,
		'ipaddr' => $remote_addr,
		'ua' => $http_user_agent,
		'timediff' => $timediff,
		'adminsession' => $adminsession
	);
	$userinfo_stmt = Database::prepare($query);
	$userinfo = Database::pexecute_first($userinfo_stmt, $userinfo_data);

	if ((($userinfo[
'adminsession'] == '1' && AREA == 'admin' && isset($userinfo['adminid'])) || ($userinfo['adminsession'] == '0' && (AREA == 'customer' || AREA == 'login') && isset($userinfo['customerid']))) && (! isset($userinfo['deactivated']) || $userinfo['deactivated'] != '1')) {
		$upd_stmt = Database::prepare("
			UPDATE `" . TABLE_PANEL_SESSIONS . "` SET
			`lastactivity` = :lastactive
			WHERE `hash` = :hash AND `adminsession` = :adminsession
		");
		$upd_data = array(
			'lastactive' => time(),
			'hash' => $s,
			'adminsession' => $adminsession
		);
		Database::pexecute($upd_stmt, $upd_data);
		$nosession = 0;
	} else {
		$nosession = 1;
	}
} else {
	$nosession = 1;
}

// Initialize our new link - class
$linker = new \Froxlor\UI\Linker('index.php', $s);

// Redirects to index.php (login page) if no session exists
if ($nosession == 1 && AREA != 'login') {
	unset($userinfo);
	$params = array(
		"script" => basename($_SERVER["SCRIPT_NAME"]),
		"qrystr" => $_SERVER["QUERY_STRING"]
	);
	\Froxlor\UI\Response::redirectTo('index.php', $params);
	exit();
}

// Logic moved out of lng-file
if (isset($userinfo['loginname']) && $userinfo['loginname'] != '') {
	$lng['menue']['main']['username'] .= $userinfo['loginname'];
	// Initialize logging
	$log = \Froxlor\FroxlorLogger::getInstanceOf($userinfo);
}


$js = "";
if (array_key_exists('js', $_themeoptions['variants'][$themevariant]) && is_array($_themeoptions['variants'][$themevariant]['js'])) {
	foreach ($_themeoptions['variants'][$themevariant]['js'] as $jsfile) {
		if (file_exists('templates/' . $theme . '/assets/js/' . $jsfile)) {
			$js .= '<script type="text/javascript" src="templates/' . $theme . '/assets/js/' . $jsfile . '"></script>' . "\n";
		}
	}
}

$css = "";
if (array_key_exists('css', $_themeoptions['variants'][$themevariant]) && is_array($_themeoptions['variants'][$themevariant]['css'])) {
	foreach ($_themeoptions['variants'][$themevariant]['css'] as $cssfile) {
		if (file_exists('templates/' . $theme . '/assets/css/' . $cssfile)) {
			$css .= '<link href="templates/' . $theme . '/assets/css/' . $cssfile . '" rel="stylesheet" type="text/css" />' . "\n";
		}
	}
}


// Initialize the mailingsystem
$mail = new \Froxlor\System\Mailer(true);
*/
