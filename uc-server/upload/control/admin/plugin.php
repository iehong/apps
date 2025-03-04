<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class pluginbase extends adminbase {

	public $plugin = [];
	public $plugins = [];

	public function __construct() {
		$this->control();
	}

	public function pluginbase() {
		parent::__construct();
		$this->check_priv();
		if(!$this->user['isfounder']) {
			$this->message('no_permission_for_this_module');
		}
		$a = getgpc('a');
		$this->load('plugin');
		$this->plugin = $_ENV['plugin']->get_plugin($a);
		$this->plugins = $_ENV['plugin']->get_plugins();
		if(empty($this->plugin)) {
			$this->message('read_plugin_invalid');
		}
		$this->view->assign('plugin', $this->plugin);
		$this->view->assign('plugins', $this->plugins);
		$this->view->languages = $this->plugin['lang'];
		$this->view->tpldir = UC_ROOT.'./plugin/'.$a;
		$this->view->objdir = UC_DATADIR.'./view';
	}

	public function _call($a, $arg) {
		$do = getgpc('do');
		$do = empty($do) ? 'onindex' : 'on'.$do;
		if(method_exists($this, $do) && $do[0] != '_') {
			$this->$do();
		} else {
			exit('Plugin module not found');
		}
	}
}

$a = getgpc('a');
$do = getgpc('do');
if(!preg_match('/^[\w]{1,64}$/', $a)) {
	exit('Argument Invalid');
}
if(!@require_once UC_ROOT."./plugin/$a/plugin.php") {
	exit('Plugin not found');
}

