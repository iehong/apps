<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class settingmodel {

	public $db;
	public $base;

	public function __construct(&$base) {
		$this->settingmodel($base);
	}

	public function settingmodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function get_settings($keys = '') {
		if($keys) {
			$keys = $this->base->implode($keys);
			$sqladd = "k IN ($keys)";
		} else {
			$sqladd = '1';
		}
		$arr = [];
		$arr = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE."settings WHERE $sqladd");
		if($arr) {
			foreach($arr as $k => $v) {
				$arr[$v['k']] = $v['v'];
				unset($arr[$k]);
			}
		}
		return $arr;
	}

}

