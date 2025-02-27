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
		if(!$this->user['isfounder'] && !$this->user['allowadminbadword']) {
			$this->message('no_permission_for_this_module');
		}
		$this->load('badword');
	}

	public function onls() {
		$page = getgpc('page');
		$find = getgpc('find', 'P');
		$replacement = getgpc('replacement', 'P');
		$replacementnew = getgpc('replacementnew', 'P');
		$findnew = getgpc('findnew', 'P');
		$delete = getgpc('delete', 'P');
		$adminscript = UC_ADMINSCRIPT;
		if($find) {
			foreach($find as $id => $arr) {
				$_ENV['badword']->update_badword($arr, $replacement[$id], $id);
			}
		}
		$status = 0;
		if($findnew) {
			$_ENV['badword']->add_badword($findnew, $replacementnew, $this->user['username']);
			$status = 1;
			$this->writelog('badword_add', 'findnew='.dhtmlspecialchars($findnew).'&replacementnew='.dhtmlspecialchars($replacementnew));
		}
		if(@$delete) {

			$_ENV['badword']->delete_badword($delete);
			$status = 2;
			$this->writelog('badword_delete', 'delete='.implode(',', $delete));
		}
		if(getgpc('multisubmit', 'P')) {
			$badwords = getgpc('badwords', 'P');
			$type = getgpc('type', 'P');
			if($type == 0) {
				$_ENV['badword']->truncate_badword();
				$type = 1;
			}
			$arr = explode("\n", str_replace(["\r", "\n\n"], ["\r", "\n"], $badwords));
			foreach($arr as $k => $v) {
				$arr2 = explode('=', $v);
				$_ENV['badword']->add_badword($arr2[0], $arr2[1], $this->user['username'], $type);
			}
		}
		if($status > 0) {
			$notedata = $_ENV['badword']->get_list($page, 1000000, 1000000);
			$this->load('note');
			$_ENV['note']->add('updatebadwords', '', $this->serialize($notedata, 1));
			$_ENV['note']->send();

			$this->load('cache');
			$_ENV['cache']->updatedata('badwords');
		}
		$num = $_ENV['badword']->get_total_num();
		$badwordlist = $_ENV['badword']->get_list($page, UC_PPP, $num);
		$multipage = $this->page($num, UC_PPP, $page, UC_ADMINSCRIPT.'?m=badword&a=ls');

		$this->view->assign('a', getgpc('a'));
		$this->view->assign('m', getgpc('m'));
		$this->view->assign('status', $status);
		$this->view->assign('badwordlist', $badwordlist);
		$this->view->assign('multipage', $multipage);

		$this->view->display('admin_badword');

	}

	public function onexport() {
		$data = $_ENV['badword']->get_list(1, 1000000, 1000000);
		$s = '';
		if($data) {
			foreach($data as $v) {
				$s .= $v['find'].'='.$v['replacement']."\r\n";
			}
		}
		@header('Content-Disposition: inline; filename=CensorWords.txt');
		@header('Content-Type: text/plain');
		echo $s;

	}

}

