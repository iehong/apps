<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class cachemodel {

	public $db;
	public $base;
	public $map;

	public function __construct(&$base) {
		$this->cachemodel($base);
	}

	public function cachemodel($base) {
		$this->base = $base;
		$this->db = $base->db;
		$this->map = [
			'settings' => ['settings'],
			'badwords' => ['badwords'],
			'plugins' => ['plugins'],
			'apps' => ['apps'],
			'fields' => ['fields'],
		];
	}

	public function updatedata($cachefile = '') {
		if($cachefile) {
			foreach((array)$this->map[$cachefile] as $modules) {
				$s = "<?php\r\n";
				foreach((array)$modules as $m) {
					$method = "_get_$m";
					$s .= '$_CACHE[\''.$m.'\'] = '.var_export($this->$method(), TRUE).";\r\n";
				}
				$s .= "\r\n?>";
				file_put_contents(UC_DATADIR."./cache/$cachefile.php", $s, LOCK_EX);
			}
		} else {
			foreach((array)$this->map as $file => $modules) {
				$s = "<?php\r\n";
				foreach($modules as $m) {
					$method = "_get_$m";
					$s .= '$_CACHE[\''.$m.'\'] = '.var_export($this->$method(), TRUE).";\r\n";
				}
				$s .= "\r\n?>";
				file_put_contents(UC_DATADIR."./cache/$file.php", $s, LOCK_EX);
			}
		}
	}

	public function updatetpl() {
		$tpl = dir(UC_DATADIR.'view');
		while($entry = $tpl->read()) {
			if(preg_match('/\.php$/', $entry)) {
				@unlink(UC_DATADIR.'view/'.$entry);
			}
		}
		$tpl->close();
	}

	private function _get_badwords() {
		$data = $this->db->fetch_all('SELECT * FROM '.UC_DBTABLEPRE.'badwords');
		$return = [];
		if(is_array($data)) {
			foreach($data as $k => $v) {
				$return['findpattern'][$k] = $v['findpattern'];
				$return['replace'][$k] = $v['replacement'];
			}
		}
		return $return;
	}

	private function _get_apps() {
		$this->base->load('app');
		$apps = $_ENV['app']->get_apps();
		$apps2 = [];
		if(is_array($apps)) {
			foreach($apps as $v) {
				$apps2[$v['appid']] = $v;
			}
		}
		return $apps2;
	}

	private function _get_settings() {
		return $this->base->get_setting();
	}

	private function _get_plugins() {
		$this->base->load('plugin');
		return $_ENV['plugin']->get_plugins();
	}

	private function _get_fields() {
		$settings = $this->base->get_setting('fields', true);
		return !empty($settings['fields']) ? array_keys($settings['fields']) : [];
	}
}

