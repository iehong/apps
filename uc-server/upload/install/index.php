<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(version_compare(PHP_VERSION, '7.0.0', '<')) {
	exit('This version of UCenter is not compatible with >= PHP 7.0, Please install or update to higher version.');
}

error_reporting(0);
@set_time_limit(1000);

const IN_COMSENZ = TRUE;
define('ROOT_PATH', dirname(__FILE__).'/../');

require ROOT_PATH.'./release/release.php';
require ROOT_PATH.'./install/var.inc.php';
require ROOT_PATH.'./install/lang.inc.php';
require ROOT_PATH.'./install/dbi.class.php';// MySQLi Only, Git新增
require ROOT_PATH.'./install/func.inc.php';

file_exists(ROOT_PATH.'./install/extvar.inc.php') && require ROOT_PATH.'./install/extvar.inc.php';

$view_off = getgpc('view_off');

define('VIEW_OFF', (bool)$view_off);

$allow_method = array('show_license', 'env_check', 'db_init', 'ext_info');

$step = intval(getgpc('step', 'R')) ? intval(getgpc('step', 'R')) : 0;
$method = getgpc('method');

if(empty($method) || !in_array($method, $allow_method)) {
	$method = isset($allow_method[$step]) ? $allow_method[$step] : '';
}

if(empty($method)) {
	show_msg('method_undefined', $method, 0);
}

if(file_exists($lockfile)) {
	show_msg('install_locked', '', 0);
} elseif(!class_exists('dbstuff')) {
	show_msg('database_nonexistence', '', 0);
}

if($method == 'show_license') {

	show_license();

} elseif($method == 'env_check') {

	VIEW_OFF && function_check($func_items);

	env_check($env_items);

	dirfile_check($dirfile_items);

	show_env_result($env_items, $dirfile_items, $func_items);

} elseif($method == 'db_init') {

	@include CONFIG;
	$submit = true;
	$error_msg = array();
	if(isset($form_db_init_items) && is_array($form_db_init_items)) {
		foreach($form_db_init_items as $key => $items) {
			$$key = getgpc($key, 'p');
			if(!isset($$key) || !is_array($$key)) {
				$submit = false;
				break;
			}
			foreach($items as $k => $v) {
				$tmp = $$key;
				$$k = addslashes($tmp[$k]);
				if(empty($$k) || !preg_match($v['reg'], $$k)) {
					if(empty($$k) && !$v['required']) {
						continue;
					}
					$submit = false;
					VIEW_OFF or $error_msg[$key][$k] = 1;
				}
			}
		}
	} else {
		$submit = false;
	}

	if(!VIEW_OFF && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if($ucfounderpw != $ucfounderpw2) {
			$error_msg['admininfo']['ucfounderpw2'] = 1;
			$submit = false;
		}

		$forceinstall = isset($_POST['dbinfo']['forceinstall']) ? $_POST['dbinfo']['forceinstall'] : '';
		$dbname_not_exists = true;
		if(!empty($dbhost) && empty($forceinstall)) {
			$dbname_not_exists = check_db($dbhost, $dbuser, $dbpw, $dbname, $tablepre);
			if(!$dbname_not_exists) {
				$form_db_init_items['dbinfo']['forceinstall'] = array('type' => 'checkbox', 'required' => 0, 'reg' => '/^.*+/');
				$error_msg['dbinfo']['forceinstall'] = 1;
				$submit = false;
				$dbname_not_exists = false;
			}
		}
		if(!empty($redisserver)) {
			list($redishost, $redisport) = explode(':', $redisserver);
			$redisport = $redisport ?? 6379;
			check_redis($redishost, $redisport, $redispw);
		}
	}

	if($submit) {

		$step = $step + 1;
		if(empty($dbname)) {
			show_msg('dbname_invalid', $dbname, 0);
		} else {
			$link = new mysqli($dbhost, $dbuser, $dbpw);
			if(!$link || $link->connect_errno > 0) {
				$errno = $link->connect_errno;
				$error = $link->connect_error;
				if($errno == 1045) {
					show_msg('database_errno_1045', $error, 0);
				} elseif($errno == 2003) {
					show_msg('database_errno_2003', $error, 0);
				} else {
					show_msg('database_connect_error', $error, 0);
				}
			}
			$link->query("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET ".DBCHARSET);

			if($link->errno) {
				show_msg('database_errno_1044', mysqli_error($link), 0);
			}
			$link->close();
		}

		if(strpos($tablepre, '.') !== false || intval($tablepre[0])) {
			show_msg('tablepre_invalid', $tablepre, 0);
		}

		config_edit();

		@set_time_limit(0);
		@ignore_user_abort(TRUE);
		ini_set('max_execution_time', 0);
		ini_set('mysql.connect_timeout', 0);

		$db = new dbstuff();
		$db->connect($dbhost, $dbuser, $dbpw, $dbname, DBCHARSET);

		$sql = file_get_contents($sqlfile);
		$sql = str_replace("\r\n", "\n", $sql);

		if(!VIEW_OFF) {
			show_header();
			show_install();
		}

		runquery($sql);

		VIEW_OFF && show_msg('initdbresult_succ');

		if(!VIEW_OFF) {
			echo '<script type="text/javascript">document.getElementById("laststep").disabled=false;document.getElementById("laststep").value = \''.lang('install_succeed').'\';</script>'."\r\n";
			show_footer();
		}

	}
	if(VIEW_OFF) {

		show_msg('missing_parameter', '', 0);

	} else {

		show_form($form_db_init_items, $error_msg);

	}

} elseif($method == 'ext_info') {

	@touch($lockfile);
	@touch(ROOT_PATH.'./data/upgrade.lock');
	if(VIEW_OFF) {
		show_msg('ext_info_succ');
	} else {

		include CONFIG;
		$md5password =  UC_FOUNDERPW;
		setcookie('uc_founderauth', authcode("|$md5password|".md5($_SERVER['HTTP_USER_AGENT'])."|1", 'ENCODE', UC_KEY), time() + 3600, '/');
		header("Location:../admin.php?m=frame&a=index&mainurl=".urlencode('admin.php?m=app&a=add'));

	}

	@unlink(ROOT_PATH.'./install/index.php');// 删除UCenter安装文件, Git新增

}