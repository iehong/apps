<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_COMSENZ')) {
	exit('Access Denied');
}

const SOFT_NAME = 'UCenter';

if(defined('UC_SERVER_VERSION')) {
	define('SOFT_VERSION', UC_SERVER_VERSION);
	define('SOFT_RELEASE', UC_SERVER_RELEASE);
} else {
	define('SOFT_VERSION', '0.0.0');
	define('SOFT_RELEASE', '19700101');
}

const INSTALL_LANG = 'SC_UTF8';

const CONFIG = ROOT_PATH.'./data/config.inc.php';

$sqlfile = ROOT_PATH.'./install/uc.sql';

$lockfile = ROOT_PATH.'./data/install.lock';

const CHARSET = 'utf-8';
const DBCHARSET = 'utf8mb4';

const ORIG_TABLEPRE = 'uc_';

const METHOD_UNDEFINED = 255;
const ENV_CHECK_RIGHT = 0;
const ERROR_CONFIG_VARS = 1;
const SHORT_OPEN_TAG_INVALID = 2;
const INSTALL_LOCKED = 3;
const DATABASE_NONEXISTENCE = 4;
const PHP_VERSION_TOO_LOW = 5;
const MYSQL_VERSION_TOO_LOW = 6;
const UC_URL_INVALID = 7;
const UC_DNS_ERROR = 8;
const UC_URL_UNREACHABLE = 9;
const UC_VERSION_INCORRECT = 10;
const UC_DBCHARSET_INCORRECT = 11;
const UC_API_ADD_APP_ERROR = 12;
const UC_ADMIN_INVALID = 13;
const UC_DATA_INVALID = 14;
const DBNAME_INVALID = 15;
const DATABASE_ERRNO_2003 = 16;
const DATABASE_ERRNO_1044 = 17;
const DATABASE_ERRNO_1045 = 18;
const DATABASE_CONNECT_ERROR = 19;
const TABLEPRE_INVALID = 20;
const CONFIG_UNWRITEABLE = 21;
const ADMIN_USERNAME_INVALID = 22;
const ADMIN_EMAIL_INVALID = 25;
const ADMIN_EXIST_PASSWORD_ERROR = 26;
const ADMININFO_INVALID = 27;
const LOCKFILE_NO_EXISTS = 28;
const TABLEPRE_EXISTS = 29;
const ERROR_UNKNOW_TYPE = 30;
const ENV_CHECK_ERROR = 31;
const UNDEFINE_FUNC = 32;
const MISSING_PARAMETER = 33;
const LOCK_FILE_NOT_TOUCH = 34;

$func_items = ['mysqli_connect', 'xml_parser_create', 'json_encode'];// MySQLi Only, Git新增

$env_items =
	[
		'os' => ['c' => 'PHP_OS', 'r' => 'notset', 'b' => 'unix'],
		'php' => ['c' => 'PHP_VERSION', 'r' => '7.0', 'b' => '7.4'],
		'redis' => ['r' => 'notset', 'b' => 'enable'],
		'attachmentupload' => ['r' => 'notset', 'b' => '2M'],
		'gdversion' => ['r' => '1.0', 'b' => '2.0'],
		'curl' => ['r' => 'notset', 'b' => 'enable'],
		'opcache' => ['r' => 'notset', 'b' => 'enable'],
		'diskspace' => ['r' => '10M', 'b' => 'notset'],
	];

$dirfile_items =
	[
		'config' => ['type' => 'file', 'path' => './data/config.inc.php'],
		'data' => ['type' => 'dir', 'path' => './data'],
		'cache' => ['type' => 'dir', 'path' => './data/cache'],
		'view' => ['type' => 'dir', 'path' => './data/view'],
		'avatar' => ['type' => 'dir', 'path' => './data/avatar'],
		'logs' => ['type' => 'dir', 'path' => './data/logs'],
		'backup' => ['type' => 'dir', 'path' => './data/backup'],
		'tmp' => ['type' => 'dir', 'path' => './data/tmp']
	];

$form_db_init_items =
	[
		'dbinfo' =>
			[
				'dbhost' => ['type' => 'text', 'required' => 1, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => 'localhost']],
				'dbname' => ['type' => 'text', 'required' => 1, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => 'ucenter']],
				'dbuser' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => 'root']],
				'dbpw' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => '']],
				'tablepre' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => 'uc_']],
				'redisserver' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => '']],
				'redispw' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => '']],
				'redispre' => ['type' => 'text', 'required' => 0, 'reg' => '/^.*$/', 'value' => ['type' => 'string', 'var' => 'uc_']],
			],
		'admininfo' =>
			[
				'ucfounderpw' => ['type' => 'password', 'required' => 1, 'reg' => '/^.*$/'],
				'ucfounderpw2' => ['type' => 'password', 'required' => 1, 'reg' => '/^.*$/'],
			]
	];