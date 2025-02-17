<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class cronmodel {

	public $db;
	public $base;

	public function __construct(&$base) {
		$this->cronmodel($base);
	}

	public function cronmodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function note_delete_user() {
	}

	public function note_delete_pm() {
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'badwords');
	}

}

