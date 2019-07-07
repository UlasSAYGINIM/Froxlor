<?php
namespace Froxlor\Frontend\Modules;

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright (c) the authors
 * @author Florian Lippert <flo@syscp.org> (2003-2009)
 * @author Froxlor team <team@froxlor.org> (2010-)
 * @license GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package Panel
 *         
 */
use Froxlor\Database\Database;
use Froxlor\Frontend\FeModule;
use Froxlor\Settings;
use Froxlor\FroxlorLogger;

class Login extends FeModule
{

	public function overview()
	{
		if (isset($_POST['send']) && $_POST['send'] == 'send') {
			$loginname = \Froxlor\Validate\Validate::validate($_POST['loginname'], 'loginname');
			$password = \Froxlor\Validate\Validate::validate($_POST['password'], 'password');

			$stmt = Database::prepare("SELECT `loginname` AS `customer` FROM `" . TABLE_PANEL_CUSTOMERS . "`
			WHERE `loginname`= :loginname");
			Database::pexecute($stmt, array(
				"loginname" => $loginname
			));
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if ($row['customer'] == $loginname) {
				$table = "`" . TABLE_PANEL_CUSTOMERS . "`";
				$uid = 'customerid';
				$adminsession = '0';
				$is_admin = false;
			} else {
				$is_admin = true;
				if ((int) Settings::Get('login.domain_login') == 1) {
					$idna_convert = new \Froxlor\Idna\IdnaWrapper();
					$domainname = $idna_convert->encode(preg_replace(array(
						'/\:(\d)+$/',
						'/^https?\:\/\//'
					), '', $loginname));
					$stmt = Database::prepare("SELECT `customerid` FROM `" . TABLE_PANEL_DOMAINS . "`
					WHERE `domain` = :domain");
					Database::pexecute($stmt, array(
						"domain" => $domainname
					));
					$row2 = $stmt->fetch(\PDO::FETCH_ASSOC);

					if (isset($row2['customerid']) && $row2['customerid'] > 0) {
						$loginname = \Froxlor\Customer\Customer::getCustomerDetail($row2['customerid'], 'loginname');
						if ($loginname !== false) {
							$stmt = Database::prepare("SELECT `loginname` AS `customer` FROM `" . TABLE_PANEL_CUSTOMERS . "`
							WHERE `loginname`= :loginname");
							Database::pexecute($stmt, array(
								"loginname" => $loginname
							));
							$row3 = $stmt->fetch(\PDO::FETCH_ASSOC);
							if ($row3['customer'] == $loginname) {
								$table = "`" . TABLE_PANEL_CUSTOMERS . "`";
								$uid = 'customerid';
								$adminsession = '0';
								$is_admin = false;
							}
						}
					}
				}
			}

			if ((\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) && $is_admin == false) {
				\Froxlor\UI\Response::redirectTo('index.php');
				exit();
			}

			if ($is_admin) {
				if (\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) {
					$stmt = Database::prepare("SELECT `loginname` AS `admin` FROM `" . TABLE_PANEL_ADMINS . "`
					WHERE `loginname`= :loginname
					AND `change_serversettings` = '1'");
					Database::pexecute($stmt, array(
						"loginname" => $loginname
					));
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);
					if (! isset($row['admin'])) {
						// not an admin who can see updates
						\Froxlor\UI\Response::redirectTo('index.php');
						exit();
					}
				} else {
					$stmt = Database::prepare("SELECT `loginname` AS `admin` FROM `" . TABLE_PANEL_ADMINS . "`
					WHERE `loginname`= :loginname");
					Database::pexecute($stmt, array(
						"loginname" => $loginname
					));
					$row = $stmt->fetch(\PDO::FETCH_ASSOC);
				}

				if ($row['admin'] == $loginname) {
					$table = "`" . TABLE_PANEL_ADMINS . "`";
					$uid = 'adminid';
					$adminsession = '1';
				} else {
					// Log failed login
					FroxlorLogger::getLog(array(
						'loginname' => $_SERVER['REMOTE_ADDR']
					))->addWarning("Unknown user '" . $loginname . "' tried to login.");

					\Froxlor\UI\Response::redirectTo('index.php', array(
						'showmessage' => '2'
					));
					exit();
				}
			}

			$userinfo_stmt = Database::prepare("SELECT * FROM $table
			WHERE `loginname`= :loginname");
			Database::pexecute($userinfo_stmt, array(
				"loginname" => $loginname
			));
			$userinfo = $userinfo_stmt->fetch(\PDO::FETCH_ASSOC);

			if ($userinfo['loginfail_count'] >= Settings::Get('login.maxloginattempts') && $userinfo['lastlogin_fail'] > (time() - Settings::Get('login.deactivatetime'))) {
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'showmessage' => '3'
				));
				exit();
			} elseif (\Froxlor\System\Crypt::validatePasswordLogin($userinfo, $password, $table, $uid)) {
				// only show "you're banned" if the login was successful
				// because we don't want to publish that the user does exist
				if ($userinfo['deactivated']) {
					unset($userinfo);
					\Froxlor\UI\Response::redirectTo('index.php', array(
						'showmessage' => '5'
					));
					exit();
				} else {
					// login correct
					// reset loginfail_counter, set lastlogin_succ
					$stmt = Database::prepare("UPDATE $table
		              SET `lastlogin_succ`= :lastlogin_succ, `loginfail_count`='0'
		              WHERE `$uid`= :uid");
					Database::pexecute($stmt, array(
						"lastlogin_succ" => time(),
						"uid" => $userinfo[$uid]
					));
					$userinfo['userid'] = $userinfo[$uid];
					$userinfo['adminsession'] = $adminsession;
				}
			} else {
				// login incorrect
				$stmt = Database::prepare("UPDATE $table
				SET `lastlogin_fail`= :lastlogin_fail, `loginfail_count`=`loginfail_count`+1
				WHERE `$uid`= :uid");
				Database::pexecute($stmt, array(
					"lastlogin_fail" => time(),
					"uid" => $userinfo[$uid]
				));

				// Log failed login
				FroxlorLogger::getLog(array(
					'loginname' => $_SERVER['REMOTE_ADDR']
				))->addWarning("User '" . $loginname . "' tried to login with wrong password.");

				unset($userinfo);
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'showmessage' => '2'
				));
				exit();
			}

			// 2FA activated
			if (Settings::Get('2fa.enabled') == '1' && $userinfo['type_2fa'] > 0) {
				// redirect to code-enter-page
				$_SESSION['secret_2fa'] = ($userinfo['type_2fa'] == 2 ? $userinfo['data_2fa'] : 'email');
				$_SESSION['uid_2fa'] = $userinfo[$uid];
				$_SESSION['uidfield_2fa'] = $uid;
				$_SESSION['uidtable_2fa'] = $table;
				$_SESSION['unfo_2fa'] = $is_admin;
				// send mail if type_2fa = 1 (email)
				if ($userinfo['type_2fa'] == 1) {
					// generate code
					$tfa = new \Froxlor\FroxlorTwoFactorAuth('Froxlor');
					$code = $tfa->getCode($tfa->createSecret());
					// set code for user
					$stmt = Database::prepare("UPDATE $table SET `data_2fa` = :d2fa WHERE `$uid` = :uid");
					Database::pexecute($stmt, array(
						"d2fa" => $code,
						"uid" => $userinfo[$uid]
					));
					// build up & send email
					$_mailerror = false;
					$this->mailerr_msg = "";
					$replace_arr = array(
						'CODE' => $code
					);
					$this->mail_body = html_entity_decode(\Froxlor\PhpHelper::replaceVariables($this->lng['mails']['2fa']['mailbody'], $replace_arr));

					try {
						$this->mail->Subject = $this->lng['mails']['2fa']['subject'];
						$this->mail->AltBody = $this->mail_body;
						$this->mail->MsgHTML(str_replace("\n", "<br />", $this->mail_body));
						$this->mail->AddAddress($userinfo['email'], \Froxlor\User::getCorrectUserSalutation($userinfo));
						$this->mail->Send();
					} catch (\PHPMailer\PHPMailer\Exception $e) {
						$this->mailerr_msg = $e->errorMessage();
						$_mailerror = true;
					} catch (\Exception $e) {
						$this->mailerr_msg = $e->getMessage();
						$_mailerror = true;
					}

					if ($_mailerror) {
						FroxlorLogger::getLog(array(
							'loginname' => '2fa code-sending'
						))->addError("Error sending mail: " . $this->mailerr_msg);
						\Froxlor\UI\Response::redirectTo('index.php', array(
							'showmessage' => '4',
							'customermail' => $userinfo['email']
						));
						exit();
					}

					$this->mail->ClearAddresses();
				}
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'module' => 'login',
					'view' => 'enter2faCode'
				));
				exit();
			}

			if (! $this->finishLogin($userinfo)) {
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'showmessage' => '2'
				));
			}
			exit();
		} else {
			$smessage = isset($_GET['showmessage']) ? (int) $_GET['showmessage'] : 0;
			$message = '';
			$successmessage = '';

			switch ($smessage) {
				case 1:
					$successmessage = $this->lng['pwdreminder']['success'];
					break;
				case 2:
					$message = $this->lng['error']['login'];
					break;
				case 3:
					$message = sprintf($this->lng['error']['login_blocked'], Settings::Get('login.deactivatetime'));
					break;
				case 4:
					$cmail = isset($_GET['customermail']) ? $_GET['customermail'] : 'unknown';
					$message = str_replace('%s', $cmail, $this->lng['error']['errorsendingmail']);
					break;
				case 5:
					$message = $this->lng['error']['user_banned'];
					break;
				case 6:
					$successmessage = $this->lng['pwdreminder']['changed'];
					break;
				case 7:
					$message = $this->lng['pwdreminder']['wrongcode'];
					break;
				case 8:
					$message = $this->lng['pwdreminder']['notallowed'];
					break;
			}

			$update_in_progress = '';
			if (\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) {
				$update_in_progress = $this->lng['update']['updateinprogress_onlyadmincanlogin'];
			}

			// Pass the last used page if needed
			$lastqrystr = "";
			if (isset($_REQUEST['qrystr']) && $_REQUEST['qrystr'] != "") {
				$lastqrystr = htmlspecialchars($_REQUEST['qrystr'], ENT_QUOTES);
			}

			\Froxlor\Frontend\UI::TwigBuffer('login/login.html.twig', array(
				'page_title' => 'Login',
				'update_in_progress' => $update_in_progress,
				'disp_login_error' => $message,
				'lastqry' => $lastqrystr
			));
		}
	}

	private function finishLogin($userinfo)
	{
		if (isset($userinfo['userid']) && $userinfo['userid'] != '') {

			\Froxlor\CurrentUser::setData($userinfo);

			$language = Settings::Get('panel.standardlanguage');
			if (isset($userinfo['def_language'])) {
				$language = $userinfo['def_language'];
			}
			\Froxlor\CurrentUser::setField('language', $language);

			if (isset($userinfo['theme']) && $userinfo['theme'] != '') {
				$theme = $userinfo['theme'];
			} else {
				$theme = Settings::Get('panel.default_theme');
				\Froxlor\CurrentUser::setField('theme', $theme);
			}

			if (Settings::Get('session.allow_multiple_login') != '1') {
				$stmt = Database::prepare("DELETE FROM `" . TABLE_PANEL_SESSIONS . "`
					WHERE `userid` = :uid
					AND `adminsession` = :adminsession");
				Database::pexecute($stmt, array(
					"uid" => $userinfo['userid'],
					"adminsession" => $userinfo['adminsession']
				));
			}

			// check for field 'theme' in session-table, refs #607
			// Changed with #1287 to new method
			$stmt = Database::query("SHOW COLUMNS FROM panel_sessions LIKE 'theme'");
			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				if ($row['Field'] == "theme") {
					$has_theme = true;
				}
			}

			$remote_addr = $_SERVER['REMOTE_ADDR'];

			if (empty($_SERVER['HTTP_USER_AGENT'])) {
				$http_user_agent = 'unknown';
			} else {
				$http_user_agent = $_SERVER['HTTP_USER_AGENT'];
			}
			$params = array(
				"hash" => 'unused',
				"userid" => $userinfo['userid'],
				"ipaddress" => $remote_addr,
				"useragent" => $http_user_agent,
				"lastactivity" => time(),
				"language" => $language,
				"adminsession" => $userinfo['adminsession']
			);

			if ($has_theme) {
				$params["theme"] = $theme;
				$stmt = Database::prepare("INSERT INTO `" . TABLE_PANEL_SESSIONS . "`
					(`hash`, `userid`, `ipaddress`, `useragent`, `lastactivity`, `language`, `adminsession`, `theme`)
					VALUES (:hash, :userid, :ipaddress, :useragent, :lastactivity, :language, :adminsession, :theme)");
			} else {
				$stmt = Database::prepare("INSERT INTO `" . TABLE_PANEL_SESSIONS . "`
					(`hash`, `userid`, `ipaddress`, `useragent`, `lastactivity`, `language`, `adminsession`)
					VALUES (:hash, :userid, :ipaddress, :useragent, :lastactivity, :language, :adminsession)");
			}
			Database::pexecute($stmt, $params);

			$qryparams = array();
			if (isset($_POST['qrystr']) && $_POST['qrystr'] != "") {
				parse_str(urldecode($_POST['qrystr']), $qryparams);
			}

			if ($userinfo['adminsession'] == '1') {
				if (\Froxlor\Froxlor::hasUpdates() || \Froxlor\Froxlor::hasDbUpdates()) {
					\Froxlor\UI\Response::redirectTo('index.php?module=AdminUpdates');
				} else {
					if (!empty($qryparams)) {
						if (preg_match("/Customer/", $qryparams['module']) === 1) {
							\Froxlor\UI\Response::redirectTo('index.php?module=AdminCustomers');
						} else {
							\Froxlor\UI\Response::redirectTo('index.php', $qryparams);
						}
					} else {
						\Froxlor\UI\Response::redirectTo('index.php?module=AdminIndex');
					}
				}
			} else {
				if (!empty($qryparams)) {
					\Froxlor\UI\Response::redirectTo('index.php', $qryparams);
				} else {
					\Froxlor\UI\Response::redirectTo('index.php?module=CustomerIndex');
				}
			}
		}
		return false;
	}

	public function enter2faCode()
	{
		// page for entering the 2FA code after successful login
		if (! isset($_SESSION) || ! isset($_SESSION['secret_2fa'])) {
			// no session - redirect to Login
			\Froxlor\UI\Response::redirectTo('index.php');
			exit();
		}
		// show template to enter code
		\Froxlor\Frontend\UI::TwigBuffer('login/entercode.html.twig', array(
			'page_title' => $this->lng['login']['2fa']
		));
	}

	public function verify2fa()
	{
		// verify code from 2fa code-enter form
		if (! isset($_SESSION) || ! isset($_SESSION['secret_2fa'])) {
			// no session - redirect to Login
			\Froxlor\UI\Response::redirectTo('index.php');
			exit();
		}
		$code = isset($_POST['2fa_code']) ? $_POST['2fa_code'] : null;
		// verify entered code
		$tfa = new \Froxlor\FroxlorTwoFactorAuth('Froxlor');
		$result = ($_SESSION['secret_2fa'] == 'email' ? true : $tfa->verifyCode($_SESSION['secret_2fa'], $code, 3));
		// either the code is valid when using authenticator-app, or we will select userdata by id and entered code
		// which is temporarily stored for the customer when using email-2fa
		if ($result) {
			// get user-data
			$table = $_SESSION['uidtable_2fa'];
			$field = $_SESSION['uidfield_2fa'];
			$uid = $_SESSION['uid_2fa'];
			$isadmin = $_SESSION['unfo_2fa'];
			$sel_param = array(
				'uid' => $uid
			);
			if ($_SESSION['secret_2fa'] == 'email') {
				// verify code by selecting user by id and the temp. stored code,
				// so only if it's the correct code, we get the user-data
				$sel_stmt = Database::prepare("SELECT * FROM $table WHERE `" . $field . "` = :uid AND `data_2fa` = :code");
				$sel_param['code'] = $code;
			} else {
				// Authenticator-verification has already happened at this point, so just get the user-data
				$sel_stmt = Database::prepare("SELECT * FROM $table WHERE `" . $field . "` = :uid");
			}
			$userinfo = Database::pexecute_first($sel_stmt, $sel_param);
			// whoops, no (valid) user? Start again
			if (empty($userinfo)) {
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'showmessage' => '2'
				));
			}
			// set fields in $userinfo required for finishLogin()
			$userinfo['adminsession'] = $isadmin;
			$userinfo['userid'] = $uid;

			// if not successful somehow - start again
			if (! $this->finishLogin($userinfo)) {
				\Froxlor\UI\Response::redirectTo('index.php', array(
					'showmessage' => '2'
				));
			}

			// when using email-2fa, remove the one-time-code
			if ($userinfo['type_2fa'] == '1') {
				$del_stmt = Database::prepare("UPDATE $table SET `data_2fa` = '' WHERE `" . $field . "` = :uid");
				$userinfo = Database::pexecute_first($del_stmt, array(
					'uid' => $uid
				));
			}
			exit();
		}
		\Froxlor\UI\Response::redirectTo('index.php', array(
			'showmessage' => '2'
		));
		exit();
	}

	public function forgotpwd()
	{
		$adminchecked = false;
		$message = '';

		if (isset($_POST['send']) && $_POST['send'] == 'send') {
			$loginname = \Froxlor\Validate\Validate::validate($_POST['loginname'], 'loginname');
			$email = \Froxlor\Validate\Validate::validateEmail($_POST['loginemail']);
			$result_stmt = Database::prepare("SELECT `adminid`, `customerid`, `firstname`, `name`, `company`, `email`, `loginname`, `def_language`, `deactivated` FROM `" . TABLE_PANEL_CUSTOMERS . "`
			WHERE `loginname`= :loginname
			AND `email`= :email");
			Database::pexecute($result_stmt, array(
				"loginname" => $loginname,
				"email" => $email
			));

			if (Database::num_rows() == 0) {
				$result_stmt = Database::prepare("SELECT `adminid`, `name`, `email`, `loginname`, `def_language`, `deactivated` FROM `" . TABLE_PANEL_ADMINS . "`
				WHERE `loginname`= :loginname
				AND `email`= :email");
				Database::pexecute($result_stmt, array(
					"loginname" => $loginname,
					"email" => $email
				));

				if (Database::num_rows() > 0) {
					$adminchecked = true;
				} else {
					$result_stmt = null;
				}
			}

			if ($result_stmt !== null) {
				$user = $result_stmt->fetch(\PDO::FETCH_ASSOC);

				/* Check whether user is banned */
				if ($user['deactivated']) {
					\Froxlor\UI\Response::redirectTo('index.php', array(
						'showmessage' => '8'
					));
					exit();
				}

				if (($adminchecked && Settings::Get('panel.allow_preset_admin') == '1') || $adminchecked == false) {
					if ($user !== false) {
						// build a activation code
						$timestamp = time();
						$first = substr(md5($user['loginname'] . $timestamp . \Froxlor\PhpHelper::randomStr(16)), 0, 15);
						$third = substr(md5($user['email'] . $timestamp . \Froxlor\PhpHelper::randomStr(16)), - 15);
						$activationcode = $first . $timestamp . $third . substr(md5($third . $timestamp), 0, 10);

						// Drop all existing activation codes for this user
						$stmt = Database::prepare("DELETE FROM `" . TABLE_PANEL_ACTIVATION . "`
						WHERE `userid` = :userid
						AND `admin` = :admin");
						$params = array(
							"userid" => $adminchecked ? $user['adminid'] : $user['customerid'],
							"admin" => $adminchecked ? 1 : 0
						);
						Database::pexecute($stmt, $params);

						// Add new activation code to database
						$stmt = Database::prepare("INSERT INTO `" . TABLE_PANEL_ACTIVATION . "`
						(userid, admin, creation, activationcode)
						VALUES (:userid, :admin, :creation, :activationcode)");
						$params = array(
							"userid" => $adminchecked ? $user['adminid'] : $user['customerid'],
							"admin" => $adminchecked ? 1 : 0,
							"creation" => $timestamp,
							"activationcode" => $activationcode
						);
						Database::pexecute($stmt, $params);

						FroxlorLogger::getLog(array(
							'loginname' => 'password_reset'
						))->addWarning("User '" . $user['loginname'] . "' requested a link for setting a new password.");

						// Set together our activation link
						$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
						// this can be a fixed value to avoid potential exploiting by modifying headers
						$host = Settings::Get('system.hostname'); // $_SERVER['HTTP_HOST'];
						$port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
						// don't add :443 when https is used, as it is default (and just looks weird!)
						if ($protocol == 'https' && $_SERVER['SERVER_PORT'] == '443') {
							$port = '';
						}
						// there can be only one script to handle this so we can use a fixed value here
						$script = "/index.php?module=Login"; // $_SERVER['SCRIPT_NAME'];
						$activationlink = $protocol . '://' . $host . $port . $script . '?view=resetpwd&resetcode=' . $activationcode;

						$replace_arr = array(
							'SALUTATION' => \Froxlor\User::getCorrectUserSalutation($user),
							'USERNAME' => $loginname,
							'LINK' => $activationlink
						);

						$def_language = ($user['def_language'] != '') ? $user['def_language'] : Settings::Get('panel.standardlanguage');
						$result_stmt = Database::prepare('SELECT `value` FROM `' . TABLE_PANEL_TEMPLATES . '`
						WHERE `adminid`= :adminid
						AND `language`= :lang
						AND `templategroup`=\'mails\'
						AND `varname`=\'password_reset_subject\'');
						Database::pexecute($result_stmt, array(
							"adminid" => $user['adminid'],
							"lang" => $def_language
						));
						$result = $result_stmt->fetch(\PDO::FETCH_ASSOC);
						$this->mail_subject = html_entity_decode(\Froxlor\PhpHelper::replaceVariables((($result['value'] != '') ? $result['value'] : $this->lng['mails']['password_reset']['subject']), $replace_arr));

						$result_stmt = Database::prepare('SELECT `value` FROM `' . TABLE_PANEL_TEMPLATES . '`
						WHERE `adminid`= :adminid
						AND `language`= :lang
						AND `templategroup`=\'mails\'
						AND `varname`=\'password_reset_mailbody\'');
						Database::pexecute($result_stmt, array(
							"adminid" => $user['adminid'],
							"lang" => $def_language
						));
						$result = $result_stmt->fetch(\PDO::FETCH_ASSOC);
						$this->mail_body = html_entity_decode(\Froxlor\PhpHelper::replaceVariables((($result['value'] != '') ? $result['value'] : $this->lng['mails']['password_reset']['mailbody']), $replace_arr));

						$_mailerror = false;
						$this->mailerr_msg = "";
						try {
							$this->mail->Subject = $this->mail_subject;
							$this->mail->AltBody = $this->mail_body;
							$this->mail->MsgHTML(str_replace("\n", "<br />", $this->mail_body));
							$this->mail->AddAddress($user['email'], \Froxlor\User::getCorrectUserSalutation($user));
							$this->mail->Send();
						} catch (\PHPMailer\PHPMailer\Exception $e) {
							$this->mailerr_msg = $e->errorMessage();
							$_mailerror = true;
						} catch (\Exception $e) {
							$this->mailerr_msg = $e->getMessage();
							$_mailerror = true;
						}

						if ($_mailerror) {
							FroxlorLogger::getLog(array(
								'loginname' => 'password_reset'
							))->addError("Error sending mail: " . $this->mailerr_msg);
							\Froxlor\UI\Response::redirectTo('index.php', array(
								'showmessage' => '4',
								'customermail' => $user['email']
							));
							exit();
						}

						$this->mail->ClearAddresses();
						\Froxlor\UI\Response::redirectTo('index.php', array(
							'showmessage' => '1'
						));
						exit();
					} else {
						FroxlorLogger::getLog(array(
							'loginname' => 'password_reset'
						))->addWarning("User '" . $loginname . "' requested to set a new password, but was not found in database!");
						$message = $this->lng['login']['combination_not_found'];
					}

					unset($user);
				}
			} else {
				$message = $this->lng['login']['usernotfound'];
			}
		}

		if ($adminchecked) {
			if (Settings::Get('panel.allow_preset_admin') != '1') {
				$message = $this->lng['pwdreminder']['notallowed'];
				unset($adminchecked);
			}
		} else {
			if (Settings::Get('panel.allow_preset') != '1') {
				$message = $this->lng['pwdreminder']['notallowed'];
			}
		}

		\Froxlor\Frontend\UI::TwigBuffer('login/fpwd.html.twig', array(
			'page_title' => 'Login',
			'disp_login_error' => $message
		));
	}

	public function resetpwd()
	{
		/*
		$message = '';

		// Remove old activation codes
		$stmt = Database::prepare("DELETE FROM `" . TABLE_PANEL_ACTIVATION . "`
		WHERE creation < :oldest");
		Database::pexecute($stmt, array(
			"oldest" => time() - 86400
		));

		if (isset($_GET['resetcode']) && strlen($_GET['resetcode']) == 50) {
			// Check if activation code is valid
			$activationcode = $_GET['resetcode'];
			$timestamp = substr($activationcode, 15, 10);
			$third = substr($activationcode, 25, 15);
			$check = substr($activationcode, 40, 10);

			if (substr(md5($third . $timestamp), 0, 10) == $check && $timestamp >= time() - 86400) {
				if (isset($_POST['send']) && $_POST['send'] == 'send') {
					$stmt = Database::prepare("SELECT `userid`, `admin` FROM `" . TABLE_PANEL_ACTIVATION . "`
					WHERE `activationcode` = :activationcode");
					$result = Database::pexecute_first($stmt, array(
						"activationcode" => $activationcode
					));

					if ($result !== false) {
						if ($result['admin'] == 1) {
							$new_password = \Froxlor\Validate\Validate::validate($_POST['new_password'], 'new password');
							$new_password_confirm = \Froxlor\Validate\Validate::validate($_POST['new_password_confirm'], 'new password confirm');
						} else {
							$new_password = \Froxlor\System\Crypt::validatePassword($_POST['new_password'], 'new password');
							$new_password_confirm = \Froxlor\System\Crypt::validatePassword($_POST['new_password_confirm'], 'new password confirm');
						}

						if ($new_password == '') {
							$message = $new_password;
						} elseif ($new_password_confirm == '') {
							$message = $new_password_confirm;
						} elseif ($new_password != $new_password_confirm) {
							$message = $new_password . " != " . $new_password_confirm;
						} else {
							// Update user password
							if ($result['admin'] == 1) {
								$stmt = Database::prepare("UPDATE `" . TABLE_PANEL_ADMINS . "`
								SET `password` = :newpassword
								WHERE `adminid` = :userid");
							} else {
								$stmt = Database::prepare("UPDATE `" . TABLE_PANEL_CUSTOMERS . "`
								SET `password` = :newpassword
								WHERE `customerid` = :userid");
							}
							Database::pexecute($stmt, array(
								"newpassword" => \Froxlor\System\Crypt::makeCryptPassword($new_password),
								"userid" => $result['userid']
							));

							FroxlorLogger::getLog(array(
								'loginname' => 'password_reset'
							))->addNotice("changed password using password reset.");

							// Remove activation code from DB
							$stmt = Database::prepare("DELETE FROM `" . TABLE_PANEL_ACTIVATION . "`
							WHERE `activationcode` = :activationcode
							AND `userid` = :userid");
							Database::pexecute($stmt, array(
								"activationcode" => $activationcode,
								"userid" => $result['userid']
							));
							\Froxlor\UI\Response::redirectTo('index.php', array(
								"showmessage" => '6'
							));
						}
					} else {
						\Froxlor\UI\Response::redirectTo('index.php', array(
							"showmessage" => '7'
						));
					}
				}

				eval("echo \"" . \Froxlor\UI\Template::getTemplate('rpwd') . "\";");
			} else {
				\Froxlor\UI\Response::redirectTo('index.php', array(
					"showmessage" => '7'
				));
			}
		} else {
			\Froxlor\UI\Response::redirectTo('index.php');
		}
		*/
	}
}
