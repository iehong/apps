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
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadminnote']) {
			$this->message('no_permission_for_this_module');
		}
		$this->load('note');
		$this->apps = $this->cache['apps'];

		$this->operations = [
			'test' => ['', 'action=test'],
			'deleteuser' => ['', 'action=deleteuser'],
			'renameuser' => ['', 'action=renameuser'],
			'deletefriend' => ['', 'action=deletefriend'],
			'gettag' => ['', 'action=gettag', 'tag', 'updatedata'],
			'getcreditsettings' => ['', 'action=getcreditsettings'],
			'updatecreditsettings' => ['', 'action=updatecreditsettings'],
			'updateclient' => ['', 'action=updateclient'],
			'updatepw' => ['', 'action=updatepw'],
			'updatebadwords' => ['', 'action=updatebadwords'],
			'updatehosts' => ['', 'action=updatehosts'],
			'updateapps' => ['', 'action=updateapps'],
			'updatecredit' => ['', 'action=updatecredit'],
		];
		$this->check_priv();
	}

	public function onls() {
		$page = getgpc('page');
		$delete = getgpc('delete', 'P');
		$status = 0;
		if(!empty($delete)) {
			$_ENV['note']->delete_note($delete);
			$status = 2;
			$this->writelog('note_delete', 'delete='.implode(',', $delete));
		}
		foreach($this->cache['apps'] as $key => $app) {
			if(empty($app['recvnote'])) {
				unset($this->apps[$key]);
			}
		}
		$num = $_ENV['note']->get_total_num(1);
		$notelist = $_ENV['note']->get_list($page, UC_PPP, $num, 1);
		$multipage = $this->page($num, UC_PPP, $page, UC_ADMINSCRIPT.'?m=note&a=ls');

		$this->view->assign('status', $status);
		$this->view->assign('applist', $this->apps);
		$this->_format_notlist($notelist);
		$this->view->assign('notelist', $notelist);
		$this->view->assign('multipage', $multipage);

		$this->view->display('admin_note');
	}

	public function onsend() {
		$noteid = intval(getgpc('noteid'));
		$appid = intval(getgpc('appid'));
		$result = $_ENV['note']->sendone($appid, $noteid);
		if($result) {
			$this->writelog('note_send', "appid=$appid&noteid=$noteid");
			$this->message('note_succeed', $_SERVER['HTTP_REFERER']);
		} else {
			$this->writelog('note_send', 'failed');
			$this->message('note_false', $_SERVER['HTTP_REFERER']);
		}

	}

	private function _note_status($status, $appid, $noteid, $args, $operation) {
		if($status > 0) {
			return '<font color="green">'.$this->lang['note_succeed'].'</font>';
		} elseif($status == 0) {
			$url = UC_ADMINSCRIPT.'?m=note&a=send&appid='.$appid.'&noteid='.$noteid;
			return '<a href="'.$url.'" class="red">'.$this->lang['note_na'].'</a>';
		} elseif($status < 0) {
			$url = UC_ADMINSCRIPT.'?m=note&a=send&appid='.$appid.'&noteid='.$noteid;
			return '<a href="'.$url.'"><font color="red">'.$this->lang['note_false'].(-$status).$this->lang['note_times'].'</font></a>';
		}
	}

	private function _format_notlist(&$notelist) {
		if(is_array($notelist)) {
			foreach($notelist as $key => $note) {
				$notelist[$key]['operation'] = $this->lang['note_'.$note['operation']];//$this->operations[$note['operation']][0];
				foreach($this->apps as $appid => $app) {
					$notelist[$key]['status'][$appid] = $this->_note_status($note['app'.$appid], $appid, $note['noteid'], '', $note['operation']);
				}
			}
		}
	}

}

