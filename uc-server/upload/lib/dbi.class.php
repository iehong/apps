<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */


class ucserver_db {
	public $querynum = 0;
	public $link;
	public $histories;
	public $stmtcache = [];

	public $dbhost;
	public $dbuser;
	public $dbpw;
	public $dbcharset;
	public $pconnect;
	public $tablepre;
	public $time;

	public $goneaway = 5;

	public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0) {
		if(intval($pconnect) === 1) $dbhost = 'p:'.$dbhost; // 前面加p:，表示persistent connection
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpw = $dbpw;
		$this->dbname = $dbname;
		$this->dbcharset = $dbcharset;
		$this->pconnect = $pconnect;
		$this->tablepre = $tablepre;
		$this->time = $time;

		mysqli_report(MYSQLI_REPORT_OFF);

		if(!$this->link = new mysqli($dbhost, $dbuser, $dbpw, $dbname)) {
			$this->halt('Can not connect to MySQL server');
		}

		$this->link->options(MYSQLI_OPT_LOCAL_INFILE, false);

		if($dbcharset) {
			$this->link->set_charset($dbcharset);
		}

		$this->link->query("SET sql_mode=''");

		$this->link->query('SET character_set_client=binary');

	}

	public function fetch_array($query, $result_type = MYSQLI_ASSOC) {
		return $query ? $query->fetch_array($result_type) : null;
	}

	public function result_first($sql) {
		$query = $this->query($sql);
		return $this->result($query, 0);
	}

	public function fetch_first($sql) {
		$query = $this->query($sql);
		return $this->fetch_array($query);
	}

	public function fetch_all($sql, $id = '') {
		$arr = [];
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$id ? $arr[$data[$id]] = $data : $arr[] = $data;
		}
		return $arr;
	}

	public function result_first_stmt($sql, $key = [], $value = []) {
		$query = $this->query_stmt($sql, $key, $value);
		return $this->result($query, 0);
	}

	public function fetch_first_stmt($sql, $key = [], $value = []) {
		$query = $this->query_stmt($sql, $key, $value);
		return $this->fetch_array($query);
	}

	public function fetch_all_stmt($sql, $key = [], $value = [], $id = '') {
		$arr = [];
		$query = $this->query_stmt($sql, $key, $value);
		while($data = $this->fetch_array($query)) {
			$id ? $arr[$data[$id]] = $data : $arr[] = $data;
		}
		return $arr;
	}

	public function cache_gc() {
		$this->query("DELETE FROM {$this->tablepre}sqlcaches WHERE expiry<$this->time");
	}

	public function query($sql, $type = '', $cachetime = FALSE) {
		$resultmode = $type == 'UNBUFFERED' ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT;
		if(!($query = $this->link->query($sql, $resultmode)) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $sql);
		}
		$this->querynum++;
		$this->histories[] = $sql;
		return $query;
	}

	public function query_stmt($sql, $key = [], $value = [], $type = '', $saveprep = FALSE, $cachetime = FALSE) {
		$parse = $this->parse_query($sql, $key, $value);
		if($saveprep && array_key_exists(hash('sha256', $parse[0]), $this->stmtcache)) {
			$stmt = &$this->stmtcache[hash('sha256', $parse[0])];
		} else {
			$stmt = $this->link->prepare($parse[0]);
			$saveprep && $this->stmtcache[hash('sha256', $parse[0])] = &$stmt;
		}
		if(!empty($key)) {
			$stmt->bind_param(...$parse[1]);
		}
		if(!($query = $stmt->execute()) && $type != 'SILENT') {
			$this->halt('MySQL Query Error', $parse[0]);
		}
		$this->querynum++;
		$this->histories[] = $parse[0];
		// SELECT 指令返回数组供其他方法使用, 其他情况返回 SQL 执行结果
		return strncasecmp('SELECT', $sql, 6) ? $query : $stmt->get_result();
	}

	public function affected_rows() {
		return $this->link->affected_rows;
	}

	public function error() {
		return $this->link->error;
	}

	public function errno() {
		return $this->link->errno;
	}

	public function result($query, $row) {
		if(!$query || $query->num_rows == 0) {
			return null;
		}
		$query->data_seek($row);
		$assocs = $query->fetch_row();
		return $assocs[0];
	}

	public function num_rows($query) {
		return $query ? $query->num_rows : 0;
	}

	public function num_fields($query) {
		return $query ? $query->field_count : 0;
	}

	public function free_result($query) {
		return $query ? $query->free() : false;
	}

	public function insert_id() {
		return ($id = $this->link->insert_id) >= 0 ? $id : $this->result($this->query('SELECT last_insert_id()'), 0);
	}

	public function fetch_row($query) {
		return $query ? $query->fetch_row() : null;
	}

	public function fetch_fields($query) {
		return $query ? $query->fetch_field() : null;
	}

	public function version() {
		return $this->link->server_info;
	}

	public function escape_string($str) {
		return $this->link->escape_string($str);
	}

	public function close() {
		return $this->link->close();
	}

	public function parse_query($sql, $key = [], $value = []) {
		$list = '';
		$array = [];
		if(strpos($sql, '?')) {// 如果SQL存在问号则使用传统匹配方式，KEY顺序与?的顺序保持一致
			foreach($key as $k => $v) {
				if(in_array($v, ['i', 'd', 's', 'b'])) {
					$list .= $v;
					$array = array_merge($array, (array)$value[$k]);
				}
			}
		} else {// 不存在问号则使用模拟PDO模式，允许在SQL内指定变量名
			preg_match_all('/:([A-Za-z0-9]*?)( |$)/', $sql, $matches);
			foreach($matches[1] as $match) {
				if(in_array($key[$match], ['i', 'd', 's', 'b'])) {
					$list .= $key[$match];
					$array = array_merge($array, (array)$value[$match]);
					$sql = str_replace(':'.$match, '?', $sql);
				}
			}
		}
		return [$sql, array_merge((array)$list, $array)];
	}

	public function halt($message = '', $sql = '') {
		$error = $this->error();
		$errorno = $this->errno();
		if($errorno == 2006 && $this->goneaway-- > 0) {
			$this->connect($this->dbhost, $this->dbuser, $this->dbpw, $this->dbname, $this->dbcharset, $this->pconnect, $this->tablepre, $this->time);
			$this->query($sql);
		} else {
			if(!empty($_ENV['showException'])) {
				throw new Exception($message.'<br />['.$errorno.'] '.$error);
			}
			$s = '';
			if($message) {
				$s = "<b>UCenter info:</b> $message<br />";
			}
			if($sql) {
				$s .= '<b>SQL:</b>'.htmlspecialchars($sql).'<br />';
			}
			$s .= '<b>Error:</b>'.$error.'<br />';
			$s .= '<b>Errno:</b>'.$errorno.'<br />';
			$s = str_replace(UC_DBTABLEPRE, '[Table]', $s);
			exit($s);
		}
	}
}

