<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class creditcontrol extends base {

	public function __construct() {
		$this->creditcontrol();
	}

	public function creditcontrol() {
		parent::__construct();
		$this->init_input();
		$this->load('note');
		$this->load('misc');
	}

	public function onrequest() {
		$uid = intval($this->input('uid'));
		$from = intval($this->input('from'));
		$to = intval($this->input('to'));
		$toappid = intval($this->input('toappid'));
		$amount = intval($this->input('amount'));
		$status = 0;
		$this->settings['creditexchange'] = @unserialize($this->settings['creditexchange']);
		if(isset($this->settings['creditexchange'][$this->app['appid'].'_'.$from.'_'.$toappid.'_'.$to])) {
			$toapp = $app = $this->cache['apps'][$toappid];
			$url = $_ENV['note']->get_url_code('updatecredit', "uid=$uid&credit=$to&amount=$amount", $toappid);
			$status = trim($_ENV['misc']->dfopen($url, 0, '', '', 1, $toapp['ip'], UC_NOTE_TIMEOUT));
		}
		echo $status ? 1 : 0;
		exit;
	}
}

