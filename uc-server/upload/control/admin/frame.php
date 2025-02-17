<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	public $members;
	public $apps;
	public $friends;

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
	}

	public function onindex() {
		$this->view->assign('sid', $this->view->sid);
		$mainurl = getgpc('mainurl');
		$mainurl = !empty($mainurl) && preg_match('/^'.UC_ADMINSCRIPT.'\.php\?(&*\w+=\w+)*$/i', $mainurl) ? $mainurl : UC_ADMINSCRIPT.'?m=frame&a=main&sid='.$this->view->sid;
		$this->view->assign('mainurl', $mainurl);
		$this->view->display('admin_frame_index');
	}

	public function onmain() {

		$members = $this->_get_uc_members();
		$applist = $this->_get_uc_apps();
		$notes = $this->_get_uc_notes();
		$errornotes = $this->_get_uc_errornotes($applist);
		$pms = $this->_get_uc_pms();
		$apps = count($applist);
		$friends = $this->_get_uc_friends();
		$this->view->assign('members', $members);
		$this->view->assign('applist', $applist);
		$this->view->assign('apps', $apps);
		$this->view->assign('friends', $friends);
		$this->view->assign('notes', $notes);
		$this->view->assign('errornotes', $errornotes);
		$this->view->assign('pms', $pms);
		$this->view->assign('iframe', getgpc('iframe', 'G'));

		if(@file_exists(constant('UC_ROOT').'./install/index.php') && !constant('UC_DEBUG')) {
			@unlink(constant('UC_ROOT').'./install/index.php');
			if(@file_exists(constant('UC_ROOT').'./install/index.php')) {
				exit('Please delete ./install/index.php via FTP!');
			}
		}

		$serverinfo = PHP_OS.' / PHP v'.PHP_VERSION;
		$dbversion = $this->db->version();
		$servername = $_SERVER['SERVER_NAME'];
		if(isset($_SERVER['SERVER_ADDR']) && isset($_SERVER['SERVER_PORT'])) {
			$servername .= ' ('.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].')';
		}
		$fileupload = @ini_get('file_uploads') ? (min(min(ini_get('upload_max_filesize'), ini_get('post_max_size')), ini_get('memory_limit'))) : '<font color="red">'.$lang['no'].'</font>';
		$dbsize = 0;
		$tablepre = UC_DBTABLEPRE;
		$query = $tables = $this->db->fetch_all("SHOW TABLE STATUS LIKE '$tablepre%'");
		foreach($tables as $table) {
			$dbsize += $table['Data_length'] + $table['Index_length'];
		}
		$dbsize = $dbsize ? $this->_sizecount($dbsize) : $lang['unknown'];
		$allow_url_fopen = ini_get('allow_url_fopen') ? 'On' : 'Off';
		$envstatus = $this->_get_uc_envstatus();
		$this->view->assign('serverinfo', $serverinfo);
		$this->view->assign('fileupload', $fileupload);
		$this->view->assign('dbsize', $dbsize);
		$this->view->assign('dbversion', $dbversion);
		$this->view->assign('servername', $servername);
		$this->view->assign('allow_url_fopen', $allow_url_fopen);
		$this->view->assign('envstatus', $envstatus);

		$this->load('upgrade');
		$_ENV['upgrade']->check();

		$this->view->display('admin_frame_main');
	}

	public function onmenu() {
		$this->view->display('admin_frame_menu');
	}

	public function onheader() {
		$this->load('app');
		$applist = $_ENV['app']->get_apps();
		$cparray = [
			'UCHOME' => 'admincp.php',
			'DISCUZ' => 'admincp.php',
			'SUPESITE' => 'admincp.php',
			'XSPACE' => 'admincp.php',
			'SUPEV' => 'admincp.php',
			'ECSHOP' => 'admin/index.php',
			'ECMALL' => 'admin.php',
			'DISCUZX' => 'admin.php'
		];
		$admincp = '';
		if(is_array($applist)) {
			foreach($applist as $k => $app) {
				if(isset($cparray[$app['type']])) {
					$admincp .= '<li><a href="'.(substr($app['url'], -1) == '/' ? $app['url'] : $app['url'].'/').$cparray[$app['type']].'" target="_blank">'.$app['name'].'</a></li>';
				}
			}
		}
		$this->view->assign('admincp', $admincp);
		$this->view->assign('username', $this->user['username']);
		$this->view->display('admin_frame_header');
	}

	private function _get_uc_members() {
		if(!$this->members) {
			$this->members = $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'members');
		}
		return $this->members;
	}

	private function _get_uc_friends() {
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'friends');
	}

	private function _get_uc_apps() {
		if(!$this->apps) {
			$this->apps = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE.'applications');
		}
		return $this->apps;
	}

	private function _get_uc_pms() {
		$pms = 0;
		for($i = 0; $i < 10; $i++) {
			$pms += $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'pm_messages_'.$i);
		}
		return $pms;
	}

	private function _get_uc_notes() {
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE."notelist WHERE closed='0'");
	}

	private function _get_uc_errornotes($applist) {
		$notelist = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE.'notelist ORDER BY dateline DESC LIMIT 20');
		$error = [];
		foreach($notelist as $note) {
			foreach($applist as $k => $app) {
				if($note['app'.$app['appid']] < 0) {
					$error[$k]++;
				}
			}
		}
		return $error;
	}

	private function _sizecount($filesize) {
		if($filesize >= 1073741824) {
			$filesize = round($filesize / 1073741824 * 100) / 100 .' GB';
		} elseif($filesize >= 1048576) {
			$filesize = round($filesize / 1048576 * 100) / 100 .' MB';
		} elseif($filesize >= 1024) {
			$filesize = round($filesize / 1024 * 100) / 100 .' KB';
		} else {
			$filesize = $filesize.' Bytes';
		}
		return $filesize;
	}

	private function _get_uc_info() {
		$update = ['uniqueid' => UC_SITEID, 'version' => UC_SERVER_VERSION, 'release' => UC_SERVER_RELEASE, 'php' => PHP_VERSION, 'mysql' => $this->db->version(), 'charset' => UC_CHARSET];
		$updatetime = @filemtime(UC_ROOT.'./data/updatetime.lock');
		if(empty($updatetime) || ($this->time - $updatetime > 3600 * 4)) {
			@touch(UC_ROOT.'./data/updatetime.lock');
			$update['members'] = $this->_get_uc_members();
			$update['friends'] = $this->_get_uc_friends();
			$apps = $this->_get_uc_apps();
			if($apps) {
				foreach($apps as $app) {
					$update['app_'.$app['appid']] = $app['name']."\t".$app['url']."\t".$app['type'];
				}
			}
		}

		$data = '';
		foreach($update as $key => $value) {
			$data .= $key.'='.rawurlencode($value).'&';
		}

		return 'update='.rawurlencode(base64_encode($data)).'&md5hash='.substr(md5($_SERVER['HTTP_USER_AGENT'].implode('', $update).$this->time), 8, 8).'&timestamp='.$this->time;
	}

	private function _get_uc_envstatus() {
		$version = constant('UC_SERVER_VERSION');
		$now_ver_gd = function_exists('gd_info') ? gd_info() : false;
		$now_ver = ['PHP' => constant('PHP_VERSION'), 'MySQL' => $this->db->version(), 'XML' => function_exists('xml_parser_create'), 'JSON' => function_exists('json_encode'), 'FileSock Function' => (function_exists('fsockopen') || function_exists('pfsockopen') || function_exists('stream_socket_client') || function_exists('curl_init')), 'GD' => ($now_ver_gd ? preg_replace('/[^0-9.]+/', '', $now_ver_gd['GD Version']) : false)];
		$req_ver = ['PHP' => '5.6.0', 'MySQL' => '5.5.3', 'XML' => true, 'JSON' => true, 'FileSock Function' => true, 'GD' => '1.0'];
		$sug_ver = ['PHP' => '7.4.0', 'MySQL' => '8.0.0', 'XML' => true, 'JSON' => true, 'FileSock Function' => true, 'GD' => '2.0'];
		foreach($now_ver as $key => $value) {
			if($req_ver[$key] === true) {
				if(!$value) {
					return ['status' => 0, 'req' => $key, 'version' => $version];
				}
			} else if(version_compare($value, $req_ver[$key], '<')) {
				return ['status' => 0, 'req' => $key, 'now_ver' => $value, 'sug_ver' => $sug_ver[$key], 'req_ver' => $req_ver[$key], 'version' => $version];
			}
		}
		return ['status' => 1, 'version' => $version];
	}



}

