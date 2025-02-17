<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends base {

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
		$authkey = md5(UC_KEY.$_SERVER['HTTP_USER_AGENT'].$this->onlineip);

		$this->time = time();
		$seccodeauth = getgpc('seccodeauth');
		$seccode = $this->authcode($seccodeauth, 'DECODE', $authkey);

		@header('Expires: -1');
		@header('Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0', FALSE);
		@header('Pragma: no-cache');

		include_once UC_ROOT.'lib/seccode.class.php';
		$code = new seccode();
		$code->code = $seccode;
		$code->type = 0;
		$code->width = 90;
		$code->height = 33;
		$code->background = 0;
		$code->adulterate = 1;
		$code->ttf = 1;
		$code->angle = 0;
		$code->color = 1;
		$code->size = 0;
		$code->shadow = 1;
		$code->animator = 0;
		$code->fontpath = UC_ROOT.'images/fonts/';
		$code->datapath = UC_ROOT.'images/';
		$code->includepath = '';
		$code->display();
	}

}

