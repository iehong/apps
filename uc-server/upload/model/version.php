<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class versionmodel {

	public $db;
	public $base;

	public function __construct(&$base) {
		$this->versionmodel($base);
	}

	public function versionmodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function check() {
		return $this->db->result_first('SELECT v FROM '.UC_DBTABLEPRE."settings WHERE k='version'");
	}

}

