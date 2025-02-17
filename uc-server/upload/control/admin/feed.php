<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	public $apps = [];
	public $operations = [];

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
		if(!$this->user['isfounder'] && !$this->user['allowadminnote']) {
			$this->message('no_permission_for_this_module');
		}
		$this->load('feed');
		$this->load('misc');
		$this->apps = $this->cache['apps'];
		$this->check_priv();
	}

	public function onls() {
		$page = getgpc('page');
		$delete = getgpc('delete', 'P');
		$num = $_ENV['feed']->get_total_num();
		$feedlist = $_ENV['feed']->get_list($page, UC_PPP, $num);
		$multipage = $this->page($num, UC_PPP, $page, UC_ADMINSCRIPT.'?m=feed&a=ls');

		$this->view->assign('feedlist', $feedlist);
		$this->view->assign('multipage', $multipage);

		$this->view->display('admin_feed');
	}

}

