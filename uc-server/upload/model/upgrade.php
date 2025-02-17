<?php

class upgrademodel {

	public $db;
	public $base;

	public function __construct(&$base) {
		$this->upgrademodel($base);
	}

	public function upgrademodel($base) {
		$this->base = $base;
		$this->db = $base->db;
	}

	public function check() {
		$settings = $this->base->get_setting('upgrade_check');
		if(!empty($settings['upgrade_check'])) {
			return;
		}

		$_ENV['showException'] = true;
		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'members');
		} catch (Exception $e) {
		}
		$checked = false;
		$secmobicc = $secmobile = false;
		foreach($fields as $row) {
			if($row['Field'] == 'password' && $row['Type'] != 'varchar(255)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members CHANGE `password` `password` varchar(255) NOT NULL DEFAULT \'\'');
				$checked[] = 'members.password';
			}
			if($row['Field'] == 'email' && $row['Type'] != 'varchar(255)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members CHANGE `email` `email` varchar(255) NOT NULL DEFAULT \'\'');
				$checked[] = 'members.email';
			}
			if($row['Field'] == 'regip' && $row['Type'] != 'varchar(45)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members CHANGE `regip` `regip` varchar(45) NOT NULL DEFAULT \'\'');
				$checked[] = 'members.regip';
			}
			if($row['Field'] == 'salt' && $row['Type'] != 'varchar(20)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members CHANGE `salt` `salt` varchar(20) NOT NULL DEFAULT \'\'');
				$checked[] = 'members.salt';
			}
			if($row['Field'] == 'secmobicc') {
				$secmobicc = true;
			}
			if($row['Field'] == 'secmobile') {
				$secmobile = true;
			}
		}
		if(!$secmobicc) {
			$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members ADD `secmobicc` varchar(3) NOT NULL DEFAULT \'\'');
			$checked[] = 'members.secmobicc';
		}
		if(!$secmobile) {
			$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members ADD `secmobile` varchar(12) NOT NULL DEFAULT \'\'');
			$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members ADD INDEX `secmobile`(`secmobile`, `secmobicc`)');
			$checked[] = 'members.secmobile';
		}

		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'domains');
		} catch (Exception $e) {
		}
		foreach($fields as $row) {
			if($row['Field'] == 'ip' && $row['Type'] != 'varchar(45)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'domains CHANGE `ip` `ip` varchar(45) NOT NULL DEFAULT \'\'');
				$checked[] = 'domains.ip';
			}
		}

		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'failedlogins');
		} catch (Exception $e) {
		}
		foreach($fields as $row) {
			if($row['Field'] == 'ip' && $row['Type'] != 'varchar(45)') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'failedlogins CHANGE `ip` `ip` varchar(45) NOT NULL DEFAULT \'\'');
				$checked[] = 'failedlogins.ip';
			}
			if($row['Field'] == 'count' && $row['Type'] != 'tinyint(3) unsigned') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'failedlogins CHANGE `count` `count` tinyint(3) unsigned NOT NULL DEFAULT \'0\'');
				$checked[] = 'failedlogins.count';
			}
		}

		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'protectedmembers');
		} catch (Exception $e) {
		}
		foreach($fields as $row) {
			if($row['Field'] == 'protectedmembers' && $row['Type'] != 'tinyint(3) unsigned') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'protectedmembers CHANGE `appid` `appid` tinyint(3) unsigned NOT NULL DEFAULT \'0\'');
				$checked[] = 'failedlogins.appid';
			}
		}

		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'pm_lists');
		} catch (Exception $e) {
		}
		foreach($fields as $row) {
			if($row['Field'] == 'pmtype' && $row['Type'] != 'tinyint(3) unsigned') {
				$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'pm_lists CHANGE `pmtype` `pmtype` tinyint(3) unsigned NOT NULL DEFAULT \'0\'');
				$checked[] = 'pm_lists.pmtype';
			}
		}

		try {
			$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'memberlogs');
		} catch (Exception $e) {
			$fields = null;
		}
		if(!$fields) {
			$this->_createtable("CREATE TABLE ".UC_DBTABLEPRE."memberlogs (lid int(10) unsigned NOT NULL AUTO_INCREMENT, uid mediumint(8) unsigned NOT NULL, action varchar(32) NOT NULL DEFAULT '', extra varchar(255) NOT NULL DEFAULT '', PRIMARY KEY(lid)) ENGINE=InnoDB;");
			$checked[] = 'memberlogs';
		}

		for($i = 0; $i < 10; $i++) {
			try {
				$fields = $this->db->fetch_all('DESC '.UC_DBTABLEPRE.'pm_messages_'.$i);
			} catch (Exception $e) {
			}
			foreach($fields as $row) {
				if($row['Field'] == 'delstatus' && $row['Type'] != 'tinyint(3) unsigned') {
					$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'pm_messages_'.$i.' CHANGE `delstatus` `delstatus` tinyint(3) unsigned NOT NULL DEFAULT \'0\'');
					$checked[] = 'pm_messages_'.$i.'.delstatus';
				}
			}
		}

		$_ENV['showException'] = false;

		if(!$checked) {
			$this->base->set_setting('upgrade_check', time());
		}
	}

	private function _createtable($sql) {
		$type = strtoupper(preg_replace('/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU', "\\2", $sql));
		$type = in_array($type, ['INNODB', 'MYISAM', 'HEAP', 'MEMORY']) ? $type : 'INNODB';
		$sql = preg_replace('/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU', "\\1", $sql)." ENGINE=$type DEFAULT CHARSET=".UC_DBCHARSET.(UC_DBCHARSET === 'utf8mb4' ? ' COLLATE=utf8mb4_unicode_ci' : '');
		$this->db->query($sql);
	}

}