<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	var $redis = null;

	public function __construct() {
		$this->control();

		require_once UC_ROOT.'lib/redis.class.php';
		$this->redis = (new ucserver_redis())::instance();
		if(!$this->redis->enable) {
			$this->message('未开启 Redis', 'BACK');
		}
	}

	public function control() {
		parent::__construct();
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadminapp']) {
			$this->message('no_permission_for_this_module');
		}
		require_once UC_ROOT.'lib/restful.class.php';
		$_ENV['restful'] = new restful();
	}

	public function onls() {
		$settings = $this->get_setting('restfulapis', true);
		$apilist = $settings['restfulapis'];
		if($this->submitcheck() && !empty($_POST['delete'])) {
			foreach($_POST['delete'] as $k => $appid) {
				unset($apilist[$appid]);
				$this->memory('rm', [$_ENV['restful']::AppIdConfPre.$appid]);
			}
			$this->set_setting('restfulapis', $apilist, true);
		}

		$a = getgpc('a');
		$this->view->assign('a', $a);
		$this->view->assign('apilist', $apilist);
		$this->view->display('admin_restful');
	}

	public function onadd() {
		if(!$this->submitcheck()) {
			$a = getgpc('a');
			$this->view->assign('a', $a);
			$this->view->display('admin_restful');
		} else {
			$settings = $this->get_setting('restfulapis', true);
			$apilist = $settings['restfulapis'];

			$appid = '1'.sprintf('%07d', $_ENV['restful']->random(7, true));
			$secret = strtoupper($_ENV['restful']->random(16));
			$name = getgpc('name', 'P');
			$apilist[$appid] = ['appid' => $appid, 'secret' => $secret, 'name' => $name];
			$this->set_setting('restfulapis', $apilist, true);

			$this->memory('set', [$_ENV['restful']::AppIdConfPre.$appid, ['appid' => $appid, 'secret' => $secret]]);

			header('location: '.UC_ADMINSCRIPT."?m=restful&a=detail&appid=$appid&addapi=yes&sid=".$this->view->sid);
		}
	}

	public function memory($method, $param) {
		return call_user_func_array([$this->redis, $method], $param);
	}

	public function ondetail() {
		$a = getgpc('a');
		$appid = getgpc('appid');
		$updated = false;
		$settings = $this->get_setting('restfulapis', true);
		$apilist = $settings['restfulapis'];

		if($this->submitcheck()) {
			$name = getgpc('name', 'P');
			$callback = getgpc('callback', 'P');
			$apilist[$appid]['perm'] = $perm = getgpc('perm', 'P');
			$apilist[$appid]['freq'] = $freq = getgpc('freq', 'P');
			$apilist[$appid]['name'] = $name;
			$apilist[$appid]['callback'] = $callback;
			$this->set_setting('restfulapis', $apilist, true);

			$apis = $freqs = [];
			foreach($perm as $k => $v) {
				$apis[$k] = true;
			}
			foreach($freq as $k => $v) {
				$freqs[$k] = $v;
			}

			if($callback) {
				$callback = explode("\n", $callback);
				$callback = array_map('trim', $callback);
			}

			$this->memory('set', [$_ENV['restful']::AppIdConfPre.$appid, [
				'appid' => $appid,
				'secret' => $apilist[$appid]['secret'],
				'apis' => $apis,
				'freq' => $freqs,
				'callback' => $callback,
			]]);

			$updated = true;
		}

		$this->view->assign('a', $a);
		$this->view->assign('addapi', getgpc('addapi'));
		$this->view->assign('updated', $updated);
		$this->view->assign('api', $apilist[$appid]);
		$this->view->assign('apis', $this->_getapis());
		$this->view->display('admin_restful');
	}

	public function onindex() {
		$this->view->assign('a', getgpc('a'));
		$this->view->assign('apis', $this->_getapis());
		$this->view->display('admin_restful');
	}

	private function _getapis() {
		$apis = $this->memory('get', ['apis']);
		if(!$apis) {
			foreach(glob(UC_ROOT.'control/*.php') as $file) {
				require_once $file;
				$m = substr(basename($file), 0, -4);
				$c = $m.'control';
				if(!class_exists($c)) {
					continue;
				}
				$code = file($file);
				foreach(get_class_methods($c) as $name) {
					if(substr($name, 0, 2) != 'on') {
						continue;
					}
					$action = substr($name, 2);
					$apis[$m][] = $action;
				}
			}
			$this->memory('set', ['apis', $apis, 86400]);
		}
		return $apis;
	}
}

