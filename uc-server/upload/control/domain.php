<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class domaincontrol extends base {

	public function __construct() {
		$this->domaincontrol();
	}

	public function domaincontrol() {
		parent::__construct();
		$this->init_input();
		$this->load('domain');
	}

	public function onls() {
		return $_ENV['domain']->get_list(1, 9999, 9999);
	}
}

