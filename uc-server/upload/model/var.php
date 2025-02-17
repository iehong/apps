<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class base_var {

	private static $instance;
	public $sid;
	public $time;
	public $onlineip;
	public $db;
	public $settings = [];
	public $cache = [];
	public $_CACHE = [];
	public $app = [];
	public $user = [];
	public $lang = [];
	public $input = [];

	public static function bind($class) {
		if(empty(self::$instance)) {
			self::$instance = new base_var();
		}
		$class->sid =& self::$instance->sid;
		$class->time =& self::$instance->time;
		$class->onlineip =& self::$instance->onlineip;
		$class->db =& self::$instance->db;
		$class->settings =& self::$instance->settings;
		$class->cache =& self::$instance->cache;
		$class->_CACHE =& self::$instance->_CACHE;
		$class->app =& self::$instance->app;
		$class->user =& self::$instance->user;
		$class->lang =& self::$instance->lang;
		$class->input =& self::$instance->input;
	}

}

