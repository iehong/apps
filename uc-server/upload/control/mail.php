<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class mailcontrol extends base {

	public function __construct() {
		$this->mailcontrol();
	}

	public function mailcontrol() {
		parent::__construct();
		$this->init_input();
	}

	public function onadd() {
		$this->load('mail');
		$mail = [];
		$mail['appid'] = $this->app['appid'];
		$mail['uids'] = explode(',', $this->input('uids'));
		$mail['emails'] = explode(',', $this->input('emails'));
		$mail['subject'] = $this->input('subject');
		$mail['message'] = $this->input('message');
		$mail['charset'] = $this->input('charset');
		$mail['htmlon'] = intval($this->input('htmlon'));
		$mail['level'] = abs(intval($this->input('level')));
		$mail['frommail'] = $this->input('frommail');
		$mail['dateline'] = $this->time;
		return $_ENV['mail']->add($mail);
	}

}

