<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

error_reporting(0);

$code = @$_GET['code'];
$apptype = @$_GET['apptype'];

$apptype = strtolower($apptype);

const IN_COMSENZ = TRUE;
if($apptype == 'discuzx') {
	define('ROOT_PATH', dirname(__FILE__).'/../../');
} else {
	define('ROOT_PATH', dirname(__FILE__).'/../');
}
const EXPLOR_SUCCESS = 0;
const IMPORT_SUCCESS = 0;
const DELETE_SQLPATH_SUCCESS = 4;
const MKDIR_ERROR = 1;
const DATABASE_EXPORT_FILE_INVALID = 2;
const RUN_SQL_ERROR = 3;
const SQLPATH_NULL_NOEXISTS = 4;
const SQLPATH_NOMATCH_BAKFILE = 5;
const BAK_FILE_LOSE = 6;
const DIR_NO_EXISTS = 7;
const DELETE_DUMPFILE_ERROR = 8;
const DB_API_NO_MATCH = 9;

$sizelimit = 2000;
$usehex = true;

if($apptype == 'discuz') {
	require ROOT_PATH.'./config.inc.php';
} elseif($apptype == 'uchome' || $apptype == 'supesite' || $apptype == 'supev') {
	require ROOT_PATH.'./config.php';
} elseif($apptype == 'ucenter') {
	require ROOT_PATH.'./data/config.inc.php';
} elseif($apptype == 'ecmall') {
	require ROOT_PATH.'./data/inc.config.php';
} elseif($apptype == 'ecshop') {
	require ROOT_PATH.'./data/config.php';
} elseif($apptype == 'discuzx') {
	require ROOT_PATH.'./config/config_global.php';
	require ROOT_PATH.'./config/config_ucenter.php';
} else {
	api_msg('db_api_no_match', $apptype);
}

parse_str(_authcode($code, 'DECODE', UC_KEY), $get);

if(empty($get)) {
	exit('Invalid Request');
}

$timestamp = time();
if($timestamp - $get['time'] > 3600) {
	exit('Authorization has expired');
}
$get['time'] = $timestamp;

class dbstuffi {
	public $querynum = 0;
	public $link;
	public $histories;
	public $time;
	public $tablepre;

	public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0) {
		$this->time = $time;
		$this->tablepre = $tablepre;

		mysqli_report(MYSQLI_REPORT_OFF);

		$this->link = new mysqli();
		if(!$this->link->real_connect($dbhost, $dbuser, $dbpw, $dbname, null, null, MYSQLI_CLIENT_COMPRESS)) {
			$this->halt('Can not connect to MySQL server');
		}

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

	public function fetch_all($sql) {
		$arr = [];
		$query = $this->query($sql);
		while($data = $this->fetch_array($query)) {
			$arr[] = $data;
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

	public function halt($message = '', $sql = '') {
		api_msg('run_sql_error', $message.'<br /><br />'.$sql.'<br /> '.$this->link->error());
	}
}

$db = new dbstuffi();
$version = '';
if($apptype == 'discuz') {

	define('BACKUP_DIR', ROOT_PATH.'forumdata/');
	$tablepre = $tablepre;
	if(empty($dbcharset)) {
		$dbcharset = in_array(strtolower($charset), ['gbk', 'big5', 'utf-8']) ? str_replace('-', '', $charset) : '';
	}
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $dbcharset, $pconnect, $tablepre);
	define('IN_DISCUZ', true);
	include ROOT_PATH.'discuz_version.php';
	$version = DISCUZ_VERSION;

} elseif($apptype == 'uchome' || $apptype == 'supesite') {

	define('BACKUP_DIR', ROOT_PATH.'./data/');
	$tablepre = $_SC['tablepre'];
	$dbcharset = $_SC['dbcharset'];
	$db->connect($_SC['dbhost'], $_SC['dbuser'], $_SC['dbpw'], $_SC['dbname'], $dbcharset, $_SC['pconnect'], $tablepre);

} elseif($apptype == 'ucenter') {

	define('BACKUP_DIR', ROOT_PATH.'./data/backup/');
	$tablepre = UC_DBTABLEPRE;
	$dbcharset = UC_DBCHARSET;
	$db->connect(UC_DBHOST, UC_DBUSER, UC_DBPW, UC_DBNAME, $dbcharset, UC_DBCONNECT, $tablepre);

} elseif($apptype == 'ecmall') {

	define('BACKUP_DIR', ROOT_PATH.'./data/backup/');
	$tablepre = DB_PREFIX;
	$dbcharset = strtolower(str_replace('-', '', strstr(LANG, '-')));
	$cfg = parse_url(DB_CONFIG);
	if(empty($cfg['pass'])) {
		$cfg['pass'] = '';
	} else {
		$cfg['pass'] = urldecode($cfg['pass']);
	}
	$cfg['user'] = urldecode($cfg['user']);
	$cfg['path'] = str_replace('/', '', $cfg['path']);

	$db->connect($cfg['host'].':'.$cfg['port'], $cfg['user'], $cfg['pass'], $cfg['path'], $dbcharset, 0, $tablepre);

} elseif($apptype == 'supev') {

	define('BACKUP_DIR', ROOT_PATH.'data/backup/');
	$tablepre = $tablepre;
	if(empty($dbcharset)) {
		$dbcharset = in_array(strtolower($_config['output']['charset']), ['gbk', 'big5', 'utf-8']) ? str_replace('-', '', CHARSET) : '';
	}
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $dbcharset, $pconnect, $tablepre);

} elseif($apptype == 'ecshop') {

	define('BACKUP_DIR', ROOT_PATH.'data/backup/');
	$tablepre = $prefix;
	$dbcharset = 'utf8';
	$db->connect($db_host, $db_user, $db_pass, $db_name, $dbcharset, 0, $tablepre);

} elseif($apptype == 'discuzx') {

	define('BACKUP_DIR', ROOT_PATH.'data/');
	extract($_config['db']['1']);
	if(empty($dbcharset)) {
		$dbcharset = in_array(strtolower(CHARSET), ['gbk', 'big5', 'utf-8']) ? str_replace('-', '', $_config['output']['charset']) : '';
	}
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $dbcharset, $pconnect, $tablepre);
	define('IN_DISCUZ', true);
	include ROOT_PATH.'source/discuz_version.php';
	$version = DISCUZ_VERSION;

}

if($get['method'] == 'export') {

	$db->query('SET SQL_QUOTE_SHOW_CREATE=0', 'SILENT');

	$time = date('Y-m-d H:i:s', $timestamp);

	$tables = [];
	$tables = arraykeys2(fetchtablelist($tablepre), 'Name');

	if($apptype == 'discuz') {
		$query = $db->query("SELECT datatables FROM {$tablepre}plugins WHERE datatables<>''");
		while($plugin = $db->fetch_array($query)) {
			foreach(explode(',', $plugin['datatables']) as $table) {
				if($table = trim($table)) {
					$tables[] = $table;
				}
			}
		}
	}
	if($apptype == 'discuzx') {
		$query = $db->query("SELECT datatables FROM {$tablepre}common_plugin WHERE datatables<>''");
		while($plugin = $db->fetch_array($query)) {
			foreach(explode(',', $plugin['datatables']) as $table) {
				if($table = trim($table)) {
					$tables[] = $table;
				}
			}
		}
	}

	$memberexist = array_search("{$tablepre}common_member", $tables);
	if($memberexist !== FALSE) {
		unset($tables[$memberexist]);
		array_unshift($tables, "{$tablepre}common_member");
	}

	$get['volume'] = isset($get['volume']) ? intval($get['volume']) : 0;
	$get['volume'] = $get['volume'] + 1;
	$version = $version ? $version : $apptype;
	$idstring = '# Identify: '.base64_encode("$timestamp,$version,$apptype,multivol,{$get['volume']}")."\n";

	if(!isset($get['sqlpath']) || empty($get['sqlpath'])) {
		$get['sqlpath'] = 'backup_'.date('ymd', $timestamp).'_'.random(6);
		if(!mkdir(BACKUP_DIR.'./'.$get['sqlpath'])) {
			api_msg('mkdir_error', 'make dir error:'.BACKUP_DIR.'./'.$get['sqlpath']);
		}
	} else {
		$get['sqlpath'] = str_replace(['/', '\\', '.', "'"], '', $get['sqlpath']);
		if(!is_dir(BACKUP_DIR.'./'.$get['sqlpath'])) {
			if(!mkdir(BACKUP_DIR.'./'.$get['sqlpath'])) {
				api_msg('mkdir_error', 'make dir error:'.BACKUP_DIR.'./'.$get['sqlpath']);
			}
		}
	}

	if(!isset($get['backupfilename']) || empty($get['backupfilename'])) {
		$get['backupfilename'] = date('ymd', $timestamp).'_'.random(6);
	}

	$sqldump = '';
	$get['tableid'] = isset($get['tableid']) ? intval($get['tableid']) : 0;
	$get['startfrom'] = isset($get['startfrom']) ? intval($get['startfrom']) : 0;

	if(!$get['tableid'] && $get['volume'] == 1) {
		foreach($tables as $table) {
			$sqldump .= sqldumptablestruct($table);
		}
	}
	$complete = TRUE;
	for(; $complete && $get['tableid'] < count($tables) && strlen($sqldump) + 500 < $sizelimit * 1000; $get['tableid']++) {
		$sqldump .= sqldumptable($tables[$get['tableid']], strlen($sqldump));
		if($complete) {
			$get['startfrom'] = 0;
		}
	}

	!$complete && $get['tableid']--;
	$dumpfile = BACKUP_DIR.$get['sqlpath'].'/'.$get['backupfilename'].'-'.$get['volume'].'.sql';
	if(trim($sqldump)) {
		$sqldump = "$idstring".
			"# <?php exit();?>\n".
			"# $apptype Multi-Volume Data Dump Vol.{$get['volume']}\n".
			"# Time: $time\n".
			"# Type: $apptype\n".
			"# Table Prefix: $tablepre\n".
			"# $dbcharset\n".
			"# $apptype Home: https://www.discuz.vip\n".
			"# Please visit our website for newest infomation about $apptype\n".
			"# --------------------------------------------------------\n\n\n".
			$sqldump;
		$fp = fopen($dumpfile, 'cb');
		if(!($fp && flock($fp, LOCK_EX) && ftruncate($fp, 0) && fwrite($fp, $sqldump) && fflush($fp) && flock($fp, LOCK_UN) && fclose($fp))) {
			flock($fp, LOCK_UN);
			fclose($fp);
			api_msg('database_export_file_invalid', $dumpfile);
		} else {
			auto_next($get, $dumpfile);
		}
	} else {
		@touch(BACKUP_DIR.$get['sqlpath'].'/index.htm');
		api_msg('explor_success', 'explor_success');
	}

} elseif($get['method'] == 'import') {

	if(!isset($get['dumpfile']) || empty($get['dumpfile'])) {
		$get['dumpfile'] = get_dumpfile_by_path($get['sqlpath']);
		$get['volume'] = 0;
	}

	if(!preg_match('/^backup_(\d+)_\w+$/', $get['sqlpath']) || !preg_match('/^\d+_\w+\-(\d+).sql$/', $get['dumpfile'])) {
		api_msg('bak_file_lose', $get['dumpfile']);
	}

	$get['volume']++;
	$next_dumpfile = preg_replace('/^(\d+)\_(\w+)\-(\d+)\.sql$/', '\\1_\\2-'.$get['volume'].'.sql', $get['dumpfile']);
	if(!is_file(BACKUP_DIR.$get['sqlpath'].'/'.$get['dumpfile'])) {
		if(is_file(BACKUP_DIR.$get['sqlpath'].'/'.$next_dumpfile)) {
			api_msg('bak_file_lose', $get['dumpfile']);
		} else {
			api_msg('import_success', 'import_success');
		}
	}

	$sqldump = file_get_contents(BACKUP_DIR.$get['sqlpath'].'/'.$get['dumpfile']);
	$sqlquery = splitsql($sqldump);
	unset($sqldump);

	foreach($sqlquery as $sql) {
		$sql = syntablestruct(trim($sql), true, $dbcharset);

		if($sql != '') {
			$db->query($sql, 'SILENT');
			if(($sqlerror = $db->error()) && $db->errno() != 1062) {
				$db->halt('MySQL Query Error', $sql);
			}
		}
	}

	$cur_file = $get['dumpfile'];
	$get['dumpfile'] = $next_dumpfile;
	auto_next($get, BACKUP_DIR.$get['sqlpath'].'/'.$cur_file);

} elseif($get['method'] == 'ping') {

	if($get['dir'] && is_dir(BACKUP_DIR.$get['dir'])) {
		echo '1';
		exit;
	} else {
		echo '-1';
		exit;
	}

} elseif($get['method'] == 'list') {

	$str = "<root>\n";
	$directory = dir(BACKUP_DIR);
	while($entry = $directory->read()) {
		$filename = BACKUP_DIR.$entry;
		if(is_dir($filename) && preg_match('/^backup_(\d+)_\w+$/', $entry, $match)) {
			$str .= "\t<dir>\n";
			$str .= "\t\t<dirname>$filename</dirname>\n";
			$str .= "\t\t<dirdate>$match[1]</dirdate>\n";
			$str .= "\t</dir>\n";
		}
	}
	$directory->close();
	$str .= '</root>';
	send_mime_type_header();
	echo $str;
	exit;

} elseif($get['method'] == 'view') {

	$sqlpath = trim($get['sqlpath']);
	if(empty($sqlpath) || !is_dir(BACKUP_DIR.$sqlpath)) {
		api_msg('dir_no_exists', $sqlpath);
	}

	$str = "<root>\n";
	$directory = dir(BACKUP_DIR.$sqlpath);
	while($entry = $directory->read()) {
		$filename = BACKUP_DIR.$sqlpath.'/'.$entry;
		if(is_file($filename) && preg_match('/\d+_\w+\-(\d+).sql$/', $filename, $match)) {
			$str .= "\t<file>\n";
			$str .= "\t\t<file_name>$match[0]</file_name>\n";
			$str .= "\t\t<file_size>".filesize($filename)."</file_size>\n";
			$str .= "\t\t<file_num>$match[1]</file_num>\n";
			$str .= "\t\t<file_url>".str_replace(ROOT_PATH, (is_https() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/', $filename)."</file_url>\n";
			$str .= "\t\t<last_modify>".filemtime($filename)."</last_modify>\n";
			$str .= "\t</file>\n";
		}
	}
	$directory->close();
	$str .= '</root>';
	send_mime_type_header();
	echo $str;
	exit;

} elseif($get['method'] == 'delete') {

	$sqlpath = trim($get['sqlpath']);
	if(empty($sqlpath) || !is_dir(BACKUP_DIR.$sqlpath) || !preg_match('/^backup_(\d+)_\w+$/', $get['sqlpath'])) {
		api_msg('dir_no_exists', $sqlpath);
	}
	$directory = dir(BACKUP_DIR.$sqlpath);
	while($entry = $directory->read()) {
		$filename = BACKUP_DIR.$sqlpath.'/'.$entry;
		if(is_file($filename) && preg_match('/^\d+_\w+\-(\d+).sql$/', $entry) && !@unlink($filename)) {
			api_msg('delete_dumpfile_error', $filename);
		}
	}
	$directory->close();
	@rmdir(BACKUP_DIR.$sqlpath);
	api_msg('delete_sqlpath_success', 'delete_sqlpath_success');

}

function syntablestruct($sql, $version, $dbcharset) {

	if(strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
		return $sql;
	}

	$sqlversion = !(strpos($sql, 'ENGINE=') === FALSE);

	if($sqlversion === $version) {

		return $sqlversion && $dbcharset ? preg_replace(['/ character set \w+/i', '/ collate \w+/i', '/DEFAULT CHARSET=\w+/is'], ['', '', "DEFAULT CHARSET=$dbcharset"], $sql) : $sql;
	}

	if($version) {
		return preg_replace(['/TYPE=HEAP/i', '/TYPE=(\w+)/is'], ["ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"], $sql);

	} else {
		return preg_replace(['/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'], ['', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'], $sql);
	}
}

function splitsql($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = [];
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = isset($ret[$num]) ? $ret[$num] : '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= isset($query[0]) && $query[0] == '#' ? NULL : $query;
		}
		$num++;
	}
	return ($ret);
}

function get_dumpfile_by_path($path) {
	if(empty($path) || !is_dir(BACKUP_DIR.$path)) {
		api_msg('sqlpath_null_noexists', $path);
	}
	$directory = dir(BACKUP_DIR.$path);
	while($entry = $directory->read()) {
		$filename = BACKUP_DIR.$path.'/'.$entry;
		if(is_file($filename)) {
			if(preg_match('/^\d+\_\w+\-\d+\.sql$/', $entry)) {
				$file_bakfile = preg_replace('/^(\d+)\_(\w+)\-(\d+)\.sql$/', '\\1_\\2-1.sql', $entry);
				if(is_file(BACKUP_DIR.$path.'/'.$file_bakfile)) {
					return $file_bakfile;
				} else {
					api_msg('sqlpath_nomatch_bakfile', $path);
				}
			}
		}
	}
	$directory->close();
	api_msg('sqlpath_nomatch_bakfile', $path);
}

function api_msg($code, $msg) {
	$msg = htmlspecialchars($msg);
	$out = "<root>\n";
	$out .= "\t<error errorCode=\"".constant(strtoupper($code))."\" errorMessage=\"$msg\" />\n";
	$out .= "\t<fileinfo>\n";
	$out .= "\t\t<file_num></file_num>\n";
	$out .= "\t\t<file_size></file_size>\n";
	$out .= "\t\t<file_name></file_name>\n";
	$out .= "\t\t<file_url></file_url>\n";
	$out .= "\t\t<last_modify></last_modify>\n";
	$out .= "\t</fileinfo>\n";
	$out .= "\t<nexturl></nexturl>\n";
	$out .= '</root>';
	send_mime_type_header();
	echo $out;
	exit;
}

function arraykeys2($array, $key2) {
	$return = [];
	foreach($array as $val) {
		$return[] = $val[$key2];
	}
	return $return;
}

function auto_next($get, $sqlfile) {
	$next_url = (is_https() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?apptype='.$GLOBALS['apptype'].'&code='.urlencode(encode_arr($get));
	$out = "<root>\n";
	$out .= "\t<error errorCode=\"0\" errorMessage=\"ok\" />\n";
	$out .= "\t<fileinfo>\n";
	$out .= "\t\t<file_num>{$get['volume']}</file_num>\n";
	$out .= "\t\t<file_size>".filesize($sqlfile)."</file_size>\n";
	$out .= "\t\t<file_name>".basename($sqlfile)."</file_name>\n";
	$out .= "\t\t<file_url>".str_replace(ROOT_PATH, (is_https() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/', $sqlfile)."</file_url>\n";
	$out .= "\t\t<last_modify>".filemtime($sqlfile)."</last_modify>\n";
	$out .= "\t</fileinfo>\n";
	$out .= "\t<nexturl><![CDATA[$next_url]]></nexturl>\n";
	$out .= '</root>';
	send_mime_type_header();
	echo $out;
	exit;
}

function encode_arr($get) {
	$tmp = '';
	foreach($get as $key => $val) {
		$tmp .= '&'.$key.'='.$val;
	}
	return _authcode($tmp, 'ENCODE', UC_KEY);
}

function sqldumptablestruct($table) {
	global $db;

	$createtable = $db->query("SHOW CREATE TABLE $table", 'SILENT');

	if(!$db->error()) {
		$tabledump = "DROP TABLE IF EXISTS $table;\n";
	} else {
		return '';
	}

	$create = $db->fetch_row($createtable);

	if(strpos($table, '.') !== FALSE) {
		$tablename = substr($table, strpos($table, '.') + 1);
		$create[1] = str_replace("CREATE TABLE $tablename", 'CREATE TABLE '.$table, $create[1]);
	}
	$tabledump .= $create[1];

	$tablestatus = $db->fetch_first("SHOW TABLE STATUS LIKE '$table'");
	$tabledump .= ($tablestatus['Auto_increment'] ? " AUTO_INCREMENT={$tablestatus['Auto_increment']}" : '').";\n\n";
	return $tabledump;
}

function sqldumptable($table, $currsize = 0) {
	global $get, $db, $sizelimit, $startrow, $extendins, $sqlcompat, $sqlcharset, $dumpcharset, $usehex, $complete, $excepttables;

	$offset = 300;
	$tabledump = '';
	$tablefields = [];

	$query = $db->query("SHOW FULL COLUMNS FROM $table", 'SILENT');
	if(strexists($table, 'adminsessions')) {
		return;
	} elseif(!$query && $db->errno() == 1146) {
		return;
	} elseif(!$query) {
		$usehex = FALSE;
	} else {
		while($fieldrow = $db->fetch_array($query)) {
			$tablefields[] = $fieldrow;
		}
	}

	$tabledumped = 0;
	$numrows = $offset;
	$firstfield = $tablefields[0];

	while($currsize + strlen($tabledump) + 500 < $sizelimit * 1000 && $numrows == $offset) {
		if($firstfield['Extra'] == 'auto_increment') {
			$selectsql = "SELECT * FROM $table WHERE {$firstfield['Field']} > {$get['startfrom']} LIMIT $offset";
		} else {
			$selectsql = "SELECT * FROM $table LIMIT {$get['startfrom']}, $offset";
		}
		$tabledumped = 1;
		$rows = $db->query($selectsql);
		$numfields = $db->num_fields($rows);

		$numrows = $db->num_rows($rows);
		while($row = $db->fetch_row($rows)) {
			$comma = $t = '';
			for($i = 0; $i < $numfields; $i++) {
				$t .= $comma.($usehex && !empty($row[$i]) && (strexists($tablefields[$i]['Type'], 'char') || strexists($tablefields[$i]['Type'], 'text')) ? '0x'.bin2hex($row[$i]) : '\''.$db->escape_string($row[$i]).'\'');
				$comma = ',';
			}
			if(strlen($t) + $currsize + strlen($tabledump) + 500 < $sizelimit * 1000) {
				if($firstfield['Extra'] == 'auto_increment') {
					$get['startfrom'] = $row[0];
				} else {
					$get['startfrom']++;
				}
				$tabledump .= "INSERT INTO $table VALUES ($t);\n";
			} else {
				$complete = FALSE;
				break 2;
			}
		}
	}

	$tabledump .= "\n";

	return $tabledump;
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	if($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function fetchtablelist($tablepre = '') {
	global $db;
	$arr = explode('.', $tablepre);
	$dbname = isset($arr[1]) && $arr[1] ? $arr[0] : '';
	$tablepre = str_replace('_', '\_', $tablepre);
	$sqladd = $dbname ? " FROM $dbname LIKE '$arr[1]%'" : "LIKE '$tablepre%'";
	$tables = $table = [];
	$query = $db->query("SHOW TABLE STATUS $sqladd");
	while($table = $db->fetch_array($query)) {
		$table['Name'] = ($dbname ? "$dbname." : '').$table['Name'];
		$tables[] = $table;
	}
	return $tables;
}

function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	// 动态密钥长度, 通过动态密钥可以让相同的 string 和 key 生成不同的密文, 提高安全性
	$ckey_length = 4;

	$key = md5($key ? $key : UC_KEY);
	// a参与加解密, b参与数据验证, c进行密文随机变换
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

	// 参与运算的密钥组
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	// 前 10 位用于保存时间戳验证数据有效性, 10 - 26位保存 $keyb , 解密时通过其验证数据完整性
	// 如果是解码的话会从第 $ckey_length 位开始, 因为密文前 $ckey_length 位保存动态密匙以保证解密正确
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	// 产生密钥簿
	$rndkey = [];
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	// 打乱密钥簿, 增加随机性
	// 类似 AES 算法中的 SubBytes 步骤
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	// 从密钥簿得出密钥进行异或，再转成字符
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		// 这里按照算法对数据进行验证, 保证数据有效性和完整性
		// $result 01 - 10 位是时间, 如果小于当前时间或为 0 则通过
		// $result 10 - 26 位是加密时的 $keyb , 需要和入参的 $keyb 做比对
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) === substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		// 把动态密钥保存在密文里, 并用 base64 编码保证传输时不被破坏
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function strexists($haystack, $needle) {
	return !(strpos($haystack, $needle) === FALSE);
}

function send_mime_type_header($type = 'application/xml') {
	header('Content-Type: '.$type);
}

function is_https() {
	// PHP 标准服务器变量
	if(isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
		return true;
	}
	// X-Forwarded-Proto 事实标准头部, 用于反代透传 HTTPS 状态
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
		return true;
	}
	// 阿里云全站加速私有 HTTPS 状态头部
	// Git 意见反馈 https://gitee.com/Discuz/DiscuzX/issues/I3W5GP
	if(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
		return true;
	}
	// 西部数码建站助手私有 HTTPS 状态头部
	// 官网意见反馈 https://discuz.dismall.com/thread-3849819-1-1.html
	if(isset($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) != 'off') {
		return true;
	}
	// 服务器端口号兜底判断
	if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
		return true;
	}
	return false;
}
