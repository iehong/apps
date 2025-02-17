<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

const UC_NOTE_REPEAT = 2;
const UC_NOTE_TIMEOUT = 15;
const UC_NOTE_GC = 5;

const API_RETURN_FAILED = '-1';

class notemodel {

	public $db;
	public $base;
	public $apps;
	public $operations = [];
	public $notetype = 'HTTP';

	public function __construct(&$base) {
		$this->notemodel($base);
	}

	public function notemodel($base) {
		$this->base = $base;
		$this->db = $base->db;
		$this->apps = $this->base->cache('apps');
		$this->operations = [
			'test' => ['', 'action=test'],
			'deleteuser' => ['', 'action=deleteuser'],
			'renameuser' => ['', 'action=renameuser'],
			'deletefriend' => ['', 'action=deletefriend'],
			'gettag' => ['', 'action=gettag', 'tag', 'updatedata'],
			'getcreditsettings' => ['', 'action=getcreditsettings'],
			'getcredit' => ['', 'action=getcredit'],
			'updatecreditsettings' => ['', 'action=updatecreditsettings'],
			'updateclient' => ['', 'action=updateclient'],
			'updatepw' => ['', 'action=updatepw'],
			'updatebadwords' => ['', 'action=updatebadwords'],
			'updatehosts' => ['', 'action=updatehosts'],
			'updateapps' => ['', 'action=updateapps'],
			'updatecredit' => ['', 'action=updatecredit'],
		];
	}

	public function get_total_num($all = TRUE) {
		$closedadd = $all ? '' : ' WHERE closed=\'0\'';
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE."notelist $closedadd");
	}

	public function get_list($page, $ppp, $totalnum, $all = TRUE) {
		$start = $this->base->page_get_start($page, $ppp, $totalnum);
		$closedadd = $all ? '' : ' WHERE closed=\'0\'';
		$data = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE."notelist $closedadd ORDER BY dateline DESC LIMIT $start, $ppp");
		foreach((array)$data as $k => $v) {
			$data[$k]['postdata2'] = addslashes(str_replace('"', '', $data[$k]['postdata']));
			$data[$k]['getdata2'] = addslashes(str_replace('"', '', $v['getdata']));
			$data[$k]['dateline'] = $v['dateline'] ? $this->base->date($data[$k]['dateline']) : '';
		}
		return $data;
	}

	public function delete_note($ids) {
		$ids = $this->base->implode($ids);
		$this->db->query('DELETE FROM '.UC_DBTABLEPRE."notelist WHERE noteid IN ($ids)");
		return $this->db->affected_rows();
	}

	public function add($operation, $getdata = '', $postdata = '', $appids = [], $pri = 0) {
		$extra = $varextra = '';
		foreach((array)$this->apps as $appid => $app) {
			$appid = $app['appid'];
			if($appid == intval($appid)) {
				if($appids && !in_array($appid, $appids)) {
					$appadd[] = 'app'.$appid."='1'";
				} else {
					$varadd[] = "('noteexists{$appid}', '1')";
				}
			}
		}
		if($appadd) {
			$extra = implode(',', $appadd);
			$extra = $extra ? ', '.$extra : '';
		}
		if($varadd) {
			$varextra = implode(', ', $varadd);
			$varextra = $varextra ? ', '.$varextra : '';
		}
		$getdata = addslashes($getdata);
		$postdata = addslashes($postdata);
		$this->db->query('INSERT INTO '.UC_DBTABLEPRE."notelist SET getdata='$getdata', operation='$operation', pri='$pri', postdata='$postdata'$extra");
		$insert_id = $this->db->insert_id();
		$insert_id && $this->db->query('REPLACE INTO '.UC_DBTABLEPRE."vars (name, value) VALUES ('noteexists', '1')$varextra");
		return $insert_id;
	}

	public function send() {
		register_shutdown_function([$this, '_send']);
	}

	private function _send() {


		$note = $this->_get_note();
		if(empty($note)) {
			$this->db->query('REPLACE INTO '.UC_DBTABLEPRE."vars SET name='noteexists', value='0'");
			return NULL;
		}

		$closenote = TRUE;
		foreach((array)$this->apps as $appid => $app) {
			$appnotes = $note['app'.$appid];
			if($app['recvnote'] && $appnotes != 1 && $appnotes > -UC_NOTE_REPEAT) {
				$this->sendone($appid, 0, $note);
				$closenote = FALSE;
				break;
			}
		}
		if($closenote) {
			$this->db->query('UPDATE '.UC_DBTABLEPRE."notelist SET closed='1' WHERE noteid='{$note['noteid']}'");
		}

		$this->_gc();
	}

	public function sendone($appid, $noteid = 0, $note = '') {
		require_once UC_ROOT.'./lib/xml.class.php';
		$return = FALSE;
		$app = $this->apps[$appid];
		if($noteid) {
			$note = $this->_get_note_by_id($noteid);
		}
		$this->base->load('misc');
		$apifilename = isset($app['apifilename']) && $app['apifilename'] ? $app['apifilename'] : 'uc.php';
		if(!isset($app['extra']['standalone'])) {
			$url = $this->get_url_code($note['operation'], $note['getdata'], $appid);
			$note['postdata'] = str_replace(["\n", "\r"], '', $note['postdata']);
			$response = trim($_ENV['misc']->dfopen2($url, 0, $note['postdata'], '', 1, $app['ip'], UC_NOTE_TIMEOUT, TRUE));
		}

		$returnsucceed = $response != '' && ($response == 1 || is_array(xml_unserialize($response)));

		$closedsqladd = $this->_close_note($note, $this->apps, $returnsucceed, $appid) ? ",closed='1'" : '';

		if($returnsucceed) {
			if($this->operations[$note['operation']][2]) {
				$this->base->load($this->operations[$note['operation']][2]);
				$func = $this->operations[$note['operation']][3];
				$_ENV[$this->operations[$note['operation']][2]]->$func($appid, $response);
			}
			$this->db->query('UPDATE '.UC_DBTABLEPRE."notelist SET app$appid='1', totalnum=totalnum+1, succeednum=succeednum+1, dateline='{$this->base->time}' $closedsqladd WHERE noteid='{$note['noteid']}'", 'SILENT');
			$return = TRUE;
		} else {
			$this->db->query('UPDATE '.UC_DBTABLEPRE."notelist SET app$appid = app$appid-'1', totalnum=totalnum+1, dateline='{$this->base->time}' $closedsqladd WHERE noteid='{$note['noteid']}'", 'SILENT');
			$return = FALSE;
		}
		return $return;
	}

	private function _get_note() {
		return $this->db->fetch_first('SELECT * FROM '.UC_DBTABLEPRE."notelist WHERE closed='0' ORDER BY pri DESC, noteid ASC LIMIT 1");
	}

	private function _gc() {
		rand(0, UC_NOTE_GC) == 0 && $this->db->query('DELETE FROM '.UC_DBTABLEPRE."notelist WHERE closed='1'");
	}

	private function _close_note($note, $apps, $returnsucceed, $appid) {
		$note['app'.$appid] = $returnsucceed ? 1 : $note['app'.$appid] - 1;
		$appcount = count($apps);
		foreach($apps as $key => $app) {
			$appstatus = $note['app'.$app['appid']];
			if(!$app['recvnote'] || $appstatus == 1 || $appstatus <= -UC_NOTE_REPEAT) {
				$appcount--;
			}
		}
		if($appcount < 1) {
			return TRUE;
		}
	}

	private function _get_note_by_id($noteid) {
		return $this->db->fetch_first('SELECT * FROM '.UC_DBTABLEPRE."notelist WHERE noteid='$noteid'");
	}

	public function get_url_code($operation, $getdata, $appid) {
		$app = $this->apps[$appid];
		$authkey = $app['authkey'];
		$url = $app['url'];
		$apifilename = isset($app['apifilename']) && $app['apifilename'] ? $app['apifilename'] : 'uc.php';
		$action = $this->operations[$operation][1];
		$code = urlencode($this->base->authcode("$action&".($getdata ? "$getdata&" : '').'time='.$this->base->time, 'ENCODE', $authkey));
		return $url."/api/$apifilename?code=$code";
	}

}

