<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadmindomain']) {
			$this->message('no_permission_for_this_module');
		}
		$this->load('domain');
		$this->load('misc');
		$this->check_priv();
	}

	public function onls() {
		$status = 0;
		if(@$_POST['domainnew']) {
			if(!$_ENV['misc']->check_ip($_POST['ipnew'])) {
				$this->message('app_add_ip_invalid', 'BACK');
			}
			$_ENV['domain']->add_domain($_POST['domainnew'], $_POST['ipnew']);
			$status = 1;
			$this->writelog('domain_add', 'domainnew='.dhtmlspecialchars($_POST['domainnew']).'&ipnew='.dhtmlspecialchars($_POST['ipnew']));
		}
		if(@$_POST['domain']) {
			foreach($_POST['domain'] as $id => $arr) {
				if(!$_ENV['misc']->check_ip($_POST['ip'][$id])) {
					$this->message('app_add_ip_invalid', 'BACK');
				}
				$_ENV['domain']->update_domain($_POST['domain'][$id], $_POST['ip'][$id], $id);
			}
			$status = 2;
		}
		if(@$_POST['delete']) {
			$_ENV['domain']->delete_domain($_POST['delete']);
			$status = 2;
			$this->writelog('domain_delete', 'delete='.implode(',', $_POST['delete']));
		}
		if($status > 0) {
			$notedata = $_ENV['domain']->get_list($_GET['page'], 1000000, 1000000);
			$this->load('note');
			$_ENV['note']->add('updatehosts', '', $this->serialize($notedata));
			$_ENV['note']->send();
		}
		$num = $_ENV['domain']->get_total_num();
		$domainlist = $_ENV['domain']->get_list($_GET['page'], UC_PPP, $num);
		$multipage = $this->page($num, UC_PPP, $_GET['page'], UC_ADMINSCRIPT.'?m=domain&a=ls');

		$this->view->assign('status', $status);
		$this->view->assign('domainlist', $domainlist);
		$this->view->assign('multipage', $multipage);

		$this->view->display('admin_domain');

	}

}

