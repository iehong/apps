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
		$this->load('mail');
		$this->check_priv();
	}

	public function onls() {
		$page = getgpc('page');
		$delete = getgpc('delete', 'P');
		$status = 0;
		if(!empty($delete)) {
			$_ENV['mail']->delete_mail($delete);
			$status = 2;
			$this->writelog('mail_delete', 'delete='.implode(',', $delete));
		}

		$num = $_ENV['mail']->get_total_num();
		$maillist = $_ENV['mail']->get_list($page, UC_PPP, $num);
		$multipage = $this->page($num, UC_PPP, $page, UC_ADMINSCRIPT.'?m=mail&a=ls');

		$this->view->assign('status', $status);
		$this->view->assign('maillist', $maillist);
		$this->view->assign('multipage', $multipage);

		$this->view->display('admin_mail');
	}

	public function onsend() {
		$mailid = intval(getgpc('mailid'));
		$result = $_ENV['mail']->send_by_id($mailid);
		if($result) {
			$this->writelog('mail_send', "mailid=$mailid");
			$this->message('mail_succeed', $_SERVER['HTTP_REFERER']);
		} else {
			$this->writelog('mail_send', 'failed');
			$this->message('mail_false', $_SERVER['HTTP_REFERER']);
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

	private function _format_maillist(&$maillist) {
		if(is_array($maillist)) {
			foreach($maillist as $key => $note) {
				$maillist[$key]['operation'] = $this->lang['note_'.$note['operation']];//$this->operations[$note['operation']][0];
				foreach($this->apps as $appid => $app) {
					$maillist[$key]['status'][$appid] = $this->_note_status($note['app'.$appid], $appid, $note['noteid'], $note['args'], $note['operation']);
				}
			}
		}
	}

}

