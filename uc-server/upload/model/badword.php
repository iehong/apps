<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class badwordmodel {

	public $db;
	public $base;

	public function __construct(&$base) {
		$this->badwordmodel($base);
	}

	public function badwordmodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function add_badword($find, $replacement, $admin, $type = 1) {
		if($find) {
			$find = trim($find);
			$replacement = trim($replacement);
			$findpattern = $this->pattern_find($find);
			if($type == 1) {
				$id = $this->db->result_first_stmt('SELECT id FROM '.UC_DBTABLEPRE.'badwords WHERE find=?', ['s'], [$find]);
				if($id) {
					$this->db->query('UPDATE '.UC_DBTABLEPRE."badwords SET find='$find', replacement='$replacement', admin='$admin', findpattern='$findpattern' WHERE id='$id'");
				} else {
					$this->db->query('INSERT INTO '.UC_DBTABLEPRE."badwords SET find='$find', replacement='$replacement', admin='$admin', findpattern='$findpattern'", 'SILENT');
				}
			} elseif($type == 2) {
				$this->db->query('INSERT INTO '.UC_DBTABLEPRE."badwords SET find='$find', replacement='$replacement', admin='$admin', findpattern='$findpattern'", 'SILENT');
			}
		}
		return $this->db->insert_id();
	}

	public function get_total_num() {
		return $this->db->result_first('SELECT COUNT(*) FROM '.UC_DBTABLEPRE.'badwords');
	}

	public function get_list($page, $ppp, $totalnum) {
		$start = $this->base->page_get_start($page, $ppp, $totalnum);
		return $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE."badwords LIMIT $start, $ppp");
	}

	public function delete_badword($arr) {
		$badwordids = $this->base->implode($arr);
		$this->db->query('DELETE FROM '.UC_DBTABLEPRE."badwords WHERE id IN ($badwordids)");
		return $this->db->affected_rows();
	}

	public function truncate_badword() {
		$this->db->query('TRUNCATE '.UC_DBTABLEPRE.'badwords');
	}

	public function update_badword($find, $replacement, $id) {
		$findpattern = $this->pattern_find($find);
		$this->db->query('UPDATE '.UC_DBTABLEPRE."badwords SET find='$find', replacement='$replacement', findpattern='$findpattern' WHERE id='$id'");
		return $this->db->affected_rows();
	}

	public function pattern_find($find) {
		$find = preg_quote($find, "/'");
		$find = str_replace("\\", "\\\\", $find);
		$find = str_replace("'", "\\'", $find);
		return '/'.preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", $find).'/is';
	}
}

