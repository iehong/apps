<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class friendcontrol extends base {

	public function __construct() {
		$this->friendcontrol();
	}

	public function friendcontrol() {
		parent::__construct();
		$this->init_input();
		$this->load('friend');
	}

	public function ondelete() {
		$uid = intval($this->input('uid'));
		$friendids = $this->input('friendids');
		return $_ENV['friend']->delete($uid, $friendids);
	}

	public function onadd() {
		$uid = intval($this->input('uid'));
		$friendid = $this->input('friendid');
		$comment = $this->input('comment');
		return $_ENV['friend']->add($uid, $friendid, $comment);
	}

	public function ontotalnum() {
		$uid = intval($this->input('uid'));
		$direction = intval($this->input('direction'));
		return $_ENV['friend']->get_totalnum_by_uid($uid, $direction);
	}

	public function onls() {
		$uid = intval($this->input('uid'));
		$page = intval($this->input('page'));
		$pagesize = intval($this->input('pagesize'));
		$totalnum = intval($this->input('totalnum'));
		$direction = intval($this->input('direction'));
		$pagesize = $pagesize ? $pagesize : UC_PPP;
		$totalnum = $totalnum ? $totalnum : $_ENV['friend']->get_totalnum_by_uid($uid);
		return $_ENV['friend']->get_list($uid, $page, $pagesize, $totalnum, $direction);
	}
}

