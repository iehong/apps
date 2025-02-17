<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadmincredits']) {
			$this->message('no_permission_for_this_module');
		}
	}

	public function onls() {
		$appsrc = getgpc('appsrc', 'P');
		$creditsrc = getgpc('creditsrc', 'P');
		$appdesc = getgpc('appdesc', 'P');
		$creditdesc = getgpc('creditdesc', 'P');
		$ratiosrc = getgpc('ratiosrc', 'P');
		$ratiodesc = getgpc('ratiodesc', 'P');
		$delete = getgpc('delete', 'P');
		$addexchange = getgpc('addexchange', 'G');
		$delexchange = getgpc('delexchange', 'G');
		$settings = $this->get_setting(['creditexchange'], TRUE);
		$creditexchange = (isset($settings['creditexchange']) && is_array($settings['creditexchange'])) ? $settings['creditexchange'] : [];
		$appsrc = @intval($appsrc);
		$creditsrc = @intval($creditsrc);
		$appdesc = @intval($appdesc);
		$creditdesc = @intval($creditdesc);
		$ratiosrc = ($ratiosrc = @intval($ratiosrc)) > 0 ? $ratiosrc : 1;
		$ratiodesc = ($ratiodesc = @intval($ratiodesc)) > 0 ? $ratiodesc : 1;
		$status = 0;
		if(!empty($addexchange) && $this->submitcheck()) {
			if($appsrc != $appdesc) {
				$key = $appsrc.'_'.$creditsrc.'_'.$appdesc.'_'.$creditdesc;
				$creditexchange[$key] = $ratiosrc."\t".$ratiodesc;
				$this->set_setting('creditexchange', $creditexchange, TRUE);
				$this->load('cache');
				$_ENV['cache']->updatedata('settings');
				$status = 1;
				$this->writelog('credit_addexchange', $appsrc.'_'.$creditsrc.' : '.$appdesc.'_'.$creditdesc.'='.$ratiosrc.' : '.$ratiodesc);
			} else {
				$status = -1;
			}
			$settings = $this->get_setting(['creditexchange'], TRUE);
			$creditexchange = is_array($settings['creditexchange']) ? $settings['creditexchange'] : [];
		} elseif(!empty($delexchange) && $this->submitcheck()) {
			if(is_array($delete)) {
				foreach($delete as $key) {
					unset($creditexchange[$key]);
				}
				$this->set_setting('creditexchange', $creditexchange, TRUE);
				$this->load('cache');
				$_ENV['cache']->updatedata('settings');
				$status = 1;
				$this->writelog('credit_deleteexchange', 'delete='.implode(',', $delete));
			}
			$settings = $this->get_setting(['creditexchange'], TRUE);
			$creditexchange = is_array($settings['creditexchange']) ? $settings['creditexchange'] : [];
		}

		$apps = isset($this->settings['credits']) ? unserialize($this->settings['credits']) : '';
		if(is_array($creditexchange)) {
			foreach($creditexchange as $set => $ratio) {
				$tmp = [];
				list($tmp['appsrc'], $tmp['creditsrc'], $tmp['appdesc'], $tmp['creditdesc']) = explode('_', $set);
				list($tmp['ratiosrc'], $tmp['ratiodesc']) = explode("\t", $ratio);
				$tmp['creditsrc'] = $apps[$tmp['appsrc']][$tmp['creditsrc']][0];
				$tmp['creditdesc'] = $apps[$tmp['appdesc']][$tmp['creditdesc']][0];
				$tmp['appsrc'] = $this->cache['apps'][$tmp['appsrc']]['name'];
				$tmp['appdesc'] = $this->cache['apps'][$tmp['appdesc']]['name'];
				$creditexchange[$set] = $tmp;
			}
		}

		$appselect = '';
		$creditselect = [];
		if(is_array($apps)) {
			foreach($apps as $appid => $credits) {
				$appselect .= '<option value="'.$appid.'">'.$this->cache['apps'][$appid]['name'].'</option>';
				$tmp = [];
				if(is_array($credits)) {
					foreach($credits as $id => $credit) {
						$tmp[] = '['.$id.', \''.str_replace('\'', '\\\'', $credit[0]).'\']';
					}
				}
				$creditselect[$appid] = 'credit['.$appid.'] = ['.implode(',', $tmp).'];';
			}
		}

		$this->view->assign('status', $status);
		$this->view->assign('appsrc', $appsrc);
		$this->view->assign('creditsrc', $creditsrc);
		$this->view->assign('appdesc', $appdesc);
		$this->view->assign('creditdesc', $creditdesc);
		$this->view->assign('ratiosrc', $ratiosrc);
		$this->view->assign('ratiodesc', $ratiodesc);
		$this->view->assign('appselect', $appselect);
		$this->view->assign('creditselect', $creditselect);
		$this->view->assign('creditexchange', $creditexchange);

		$this->view->display('admin_credit');
	}

	public function onsync() {
		$this->load('note');
		$this->load('misc');
		$this->load('cache');
		$step = intval(getgpc('step', 'G'));
		if(!$step && is_array($this->cache['apps'])) {
			$credits = [];
			$stepapp = intval(getgpc('stepapp', 'G'));
			$testrelease = intval(getgpc('testrelease', 'G'));
			$appids = array_keys($this->cache['apps']);
			$appid = $appids[$stepapp];
			if(!$stepapp) {
				$_CACHE['credits'] = [];
			} else {
				include UC_DATADIR.'cache/credits.php';
			}
			if($app = $this->cache['apps'][$appid]) {
				$url = $_ENV['note']->get_url_code('getcreditsettings', '', $appid);
				$data = trim($_ENV['misc']->dfopen($url, 0, '', '', 1));
				if(!$testrelease) {
					if(!($data = $this->sync_unserialize($data, ''))) {
						header('location: '.UC_API.'/'.UC_ADMINSCRIPT.'?m=credit&a=sync&step=0&stepapp='.$stepapp.'&testrelease=1&sid='.$this->view->sid);
						exit();
					} else {
						$stepapp++;
					}
				} else {
					$data = $this->sync_unserialize($data, 'release/20080429/');
					$stepapp++;
				}

				if($data) {
					$_CACHE['credits'][$appid] = $data;
					$s = "<?php\r\n";
					$s .= '$_CACHE[\'credits\'] = '.var_export($_CACHE['credits'], TRUE).";\r\n";
					$s .= "\r\n?>";
					file_put_contents(UC_DATADIR.'cache/credits.php', $s, LOCK_EX);
				}
				header('location: '.UC_API.'/'.UC_ADMINSCRIPT.'?m=credit&a=sync&step=0&stepapp='.$stepapp.'&sid='.$this->view->sid);
			} else {
				header('location: '.UC_API.'/'.UC_ADMINSCRIPT.'?m=credit&a=sync&step=1&sid='.$this->view->sid);
			}
			exit();
		}

		include_once UC_DATADIR.'cache/credits.php';
		$credits = $_CACHE['credits'];
		$this->set_setting('credits', $credits, TRUE);
		$this->load('cache');
		$_ENV['cache']->updatedata('settings');
		$this->writelog('credit_sync', 'succeed');

		$settings = $this->get_setting(['creditexchange'], TRUE);
		$creditexchange = is_array($settings['creditexchange']) ? $settings['creditexchange'] : [];
		$updaterequest = [];
		$i = 0;
		foreach($creditexchange as $set => $ratio) {
			$tmp = [];
			list($tmp['appsrc'], $tmp['creditsrc'], $tmp['appdesc'], $tmp['creditdesc']) = explode('_', $set);
			list($tmp['ratiosrc'], $tmp['ratiodesc']) = explode("\t", $ratio);
			$updaterequest[$tmp['appsrc']][] =
				'&credit['.$tmp['appsrc'].']['.$i.'][creditsrc]='.intval($tmp['creditsrc']).
				'&credit['.$tmp['appsrc'].']['.$i.'][appiddesc]='.urlencode($tmp['appdesc']).
				'&credit['.$tmp['appsrc'].']['.$i.'][creditdesc]='.intval($tmp['creditdesc']).
				'&credit['.$tmp['appsrc'].']['.$i.'][title]='.urlencode($this->cache['apps'][$tmp['appdesc']]['name'].' '.$credits[$tmp['appdesc']][$tmp['creditdesc']][0]).
				'&credit['.$tmp['appsrc'].']['.$i.'][unit]='.urlencode($credits[$tmp['appdesc']][$tmp['creditdesc']][1]).
				'&credit['.$tmp['appsrc'].']['.$i.'][ratiosrc]='.$tmp['ratiosrc'].
				'&credit['.$tmp['appsrc'].']['.$i.'][ratiodesc]='.$tmp['ratiodesc'].
				'&credit['.$tmp['appsrc'].']['.$i.'][ratio]='.($tmp['ratiosrc'] / $tmp['ratiodesc']);
			$i++;
		}
		$data = [];
		foreach($updaterequest as $appid => $value) {
			$data[] = implode('', $value);
		}
		$_ENV['note']->add('updatecreditsettings', implode('', $data));
		$_ENV['note']->send();

		$this->message('syncappcredits_updated', UC_ADMINSCRIPT.'?m=credit&a=ls');
	}

	public function sync_unserialize($s, $release_root) {
		if(!function_exists('xml_unserialize')) {
			if($release_root && file_exists(UC_ROOT.$release_root.'./lib/xml.class.php')) {
				include UC_ROOT.$release_root.'./lib/xml.class.php';
			} else {
				include UC_ROOT.'./lib/xml.class.php';
			}
		}

		return xml_unserialize($s);
	}

}

