<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class feedmodel {

	public $db;
	public $base;
	public $apps;
	public $operations = [];

	public function __construct(&$base) {
		$this->feedmodel($base);
	}

	public function feedmodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function get_total_num() {
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'feeds');
	}

	public function get_list($page, $ppp, $totalnum) {
		$start = $this->base->page_get_start($page, $ppp, $totalnum);
		$data = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE."feeds LIMIT $start, $ppp");

		foreach((array)$data as $k => $v) {
			$searchs = $replaces = [];
			$title_data = $_ENV['misc']->string2array($v['title_data']);
			foreach(array_keys($title_data) as $key) {
				$searchs[] = '{'.$key.'}';
				$replaces[] = $title_data[$key];
			}
			$searchs[] = '{actor}';
			$replaces[] = $v['username'];
			$searchs[] = '{app}';
			$replaces[] = $this->base->apps[$v['appid']]['name'];
			$data[$k]['title_template'] = str_replace($searchs, $replaces, $data[$k]['title_template']);
			$data[$k]['dateline'] = $v['dateline'] ? $this->base->date($data[$k]['dateline']) : '';
		}
		return $data;
	}
}
