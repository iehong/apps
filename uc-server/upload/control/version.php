<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class versioncontrol extends base {

	public function __construct() {
		$this->versioncontrol();
	}

	public function versioncontrol() {
		parent::__construct();
		$this->load('version');
	}

	public function oncheck() {
		$db_version = $_ENV['version']->check();
		return ['file' => UC_SERVER_VERSION, 'db' => $db_version];
	}

}

