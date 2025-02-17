<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

class control extends adminbase {

	public $_setting_items = ['doublee', 'accessemail', 'censoremail', 'censorusername',
		'dateformat', 'timeoffset', 'timeformat', 'extra', 'maildefault', 'mailsend', 'mailserver',
		'mailport', 'mailtimeout', 'mailauth', 'mailfrom', 'mailauth_username', 'mailauth_password', 'maildelimiter',
		'mailusername', 'mailsilent', 'pmcenter', 'privatepmthreadlimit', 'chatpmthreadlimit',
		'chatpmmemberlimit', 'pmfloodctrl', 'sendpmseccode', 'pmsendregdays', 'login_failedtime',
		'addappbyurl', 'insecureoperation', 'passwordalgo', 'passwordoptions'];

	public function __construct() {
		$this->control();
	}

	public function control() {
		parent::__construct();
		$this->check_priv();
		if(!$this->user['isfounder'] && !$this->user['allowadminsetting']) {
			$this->message('no_permission_for_this_module');
		}
		$this->check_priv();
	}

	public function onls() {
		$this->load('user');
		$updated = false;
		if($this->submitcheck()) {
			$timeformat = getgpc('timeformat', 'P');
			$dateformat = getgpc('dateformat', 'P');
			$timeoffset = getgpc('timeoffset', 'P');
			$privatepmthreadlimit = getgpc('privatepmthreadlimit', 'P');
			$chatpmthreadlimit = getgpc('chatpmthreadlimit', 'P');
			$chatpmmemberlimit = getgpc('chatpmmemberlimit', 'P');
			$pmfloodctrl = getgpc('pmfloodctrl', 'P');
			$pmsendregdays = getgpc('pmsendregdays', 'P');
			$pmcenter = getgpc('pmcenter', 'P');
			$sendpmseccode = getgpc('sendpmseccode', 'P');
			$login_failedtime = getgpc('login_failedtime', 'P');
			$addappbyurl = getgpc('addappbyurl', 'P');
			$insecureoperation = getgpc('insecureoperation', 'P');
			$passwordalgo = getgpc('passwordalgo', 'P');
			$passwordoptions = htmlspecialchars_decode(stripslashes(getgpc('passwordoptions', 'P')));
			$username_len_max = getgpc('username_len_max', 'P');
			$dateformat = str_replace(['yyyy', 'mm', 'dd'], ['y', 'n', 'j'], strtolower($dateformat));
			$timeformat = $timeformat == 1 ? 'H:i' : 'h:i A';
			$timeoffset = in_array($timeoffset, ['-12', '-11', '-10', '-9', '-8', '-7', '-6', '-5', '-4', '-3.5', '-3', '-2', '-1', '0', '1', '2', '3', '3.5', '4', '4.5', '5', '5.5', '5.75', '6', '6.5', '7', '8', '9', '9.5', '10', '11', '12']) ? $timeoffset : 8;

			if(empty($passwordalgo) && !empty($passwordoptions)) {
				// 当密码选项配置时, 密码算法不能为空
				$passwordoptions = '';
			} else if(!empty($passwordalgo)) {
				// 有可能符合要求算法时做测试, 如果返回 false 或密码无法校验通过说明该配置不合理导致 PHP 无法处理, 则需要清除
				// 密码散列算法会在部分出错情况下返回 NULL 并报 Warning, 在此特殊处理
				$options = empty($passwordoptions) ? [] : json_decode($passwordoptions, true);
				$tresult = password_hash($passwordalgo, constant($passwordalgo), $options);
				if($tresult === false || $tresult === null || !password_verify($passwordalgo, $tresult)) {
					$passwordalgo = '';
					$passwordoptions = '';
				}
			}

			$this->set_setting('dateformat', $dateformat);
			$this->set_setting('timeformat', $timeformat);
			$timeoffset = $timeoffset * 3600;
			$this->set_setting('timeoffset', $timeoffset);
			$this->set_setting('privatepmthreadlimit', intval($privatepmthreadlimit));
			$this->set_setting('chatpmthreadlimit', intval($chatpmthreadlimit));
			$this->set_setting('chatpmmemberlimit', intval($chatpmmemberlimit));
			$this->set_setting('pmfloodctrl', intval($pmfloodctrl));
			$this->set_setting('pmsendregdays', intval($pmsendregdays));
			$this->set_setting('pmcenter', $pmcenter);
			$this->set_setting('sendpmseccode', $sendpmseccode ? 1 : 0);
			$this->set_setting('login_failedtime', intval($login_failedtime));
			$this->set_setting('addappbyurl', $addappbyurl);
			$this->set_setting('insecureoperation', $insecureoperation);
			$this->set_setting('passwordalgo', $passwordalgo);
			$this->set_setting('passwordoptions', $passwordoptions);
			if($this->_username_field('update', $username_len_max)) {
				$this->set_setting('username_len_max', $username_len_max);
			}
			$updated = true;

			$this->updatecache();
		}

		$settings = $this->get_setting($this->_setting_items);
		if($updated) {
			$this->_add_note_for_setting($settings);
		}
		$settings['dateformat'] = str_replace(['y', 'n', 'j'], ['yyyy', 'mm', 'dd'], $settings['dateformat']);
		$settings['timeformat'] = $settings['timeformat'] == 'H:i' ? 1 : 0;
		$settings['pmcenter'] = $settings['pmcenter'] ? 1 : 0;
		$settings['insecureoperation'] = $settings['insecureoperation'] ? 1 : 0;
		$a = getgpc('a');
		$this->view->assign('a', $a);
		$this->view->assign('m', getgpc('m'));

		$this->view->assign('dateformat', $settings['dateformat']);
		$timeformatchecked = ['', ''];
		$timeformatchecked[$settings['timeformat']] = 'checked="checked"';
		$this->view->assign('timeformat', $timeformatchecked);
		$this->view->assign('privatepmthreadlimit', $settings['privatepmthreadlimit']);
		$this->view->assign('chatpmthreadlimit', $settings['chatpmthreadlimit']);
		$this->view->assign('chatpmmemberlimit', $settings['chatpmmemberlimit']);
		$this->view->assign('pmsendregdays', $settings['pmsendregdays']);
		$this->view->assign('pmfloodctrl', $settings['pmfloodctrl']);
		$pmcenterchecked = ['', ''];
		$pmcenterchecked[$settings['pmcenter']] = 'checked="checked"';
		$pmcenterchecked['display'] = $settings['pmcenter'] ? '' : 'style="display:none"';
		$addappbyurlchecked = ['', ''];
		$addappbyurlchecked[$settings['addappbyurl']] = 'checked="checked"';
		$insecureoperationchecked = ['', ''];
		$insecureoperationchecked[$settings['insecureoperation']] = 'checked="checked"';
		$this->view->assign('pmcenter', $pmcenterchecked);
		$sendpmseccodechecked = ['', ''];
		$sendpmseccodechecked[$settings['sendpmseccode']] = 'checked="checked"';
		$this->view->assign('sendpmseccode', $sendpmseccodechecked);
		$this->view->assign('addappbyurl', $addappbyurlchecked);
		$this->view->assign('insecureoperation', $insecureoperationchecked);
		$this->view->assign('passwordalgo', $settings['passwordalgo']);
		$this->view->assign('passwordoptions', htmlspecialchars($settings['passwordoptions']));
		$username_len_max = $this->_username_field('get');
		if($username_len_max) {
			$this->view->assign('username_len_max', $username_len_max);
		}
		$timeoffset = intval($settings['timeoffset'] / 3600);
		$checkarray = [$timeoffset < 0 ? '0'.substr($timeoffset, 1) : $timeoffset => 'selected="selected"'];
		$this->view->assign('checkarray', $checkarray);
		$this->view->assign('updated', $updated);
		$this->view->assign('login_failedtime', $settings['login_failedtime']);
		$this->view->display('admin_setting');
	}

	public function updatecache() {
		$this->load('cache');
		$_ENV['cache']->updatedata('settings');
	}

	public function onregister() {
		$updated = false;
		if($this->submitcheck()) {
			$this->set_setting('doublee', getgpc('doublee', 'P'));
			$this->set_setting('accessemail', getgpc('accessemail', 'P'));
			$this->set_setting('censoremail', getgpc('censoremail', 'P'));
			$this->set_setting('censorusername', getgpc('censorusername', 'P'));
			$updated = true;
			$this->writelog('setting_register_update');
			$this->updatecache();
		}

		$settings = $this->get_setting($this->_setting_items);
		if($updated) {
			$this->_add_note_for_setting($settings);
		}

		$this->view->assign('a', getgpc('a'));
		$this->view->assign('m', getgpc('m'));
		$doubleechecked = ['', ''];
		$doubleechecked[$settings['doublee']] = 'checked="checked"';
		$this->view->assign('doublee', $doubleechecked);
		$this->view->assign('accessemail', $settings['accessemail']);
		$this->view->assign('censoremail', $settings['censoremail']);
		$this->view->assign('censorusername', $settings['censorusername']);
		$this->view->assign('updated', $updated);
		$this->view->display('admin_setting');
	}

	public function onmail() {
		$items = ['maildefault', 'mailsend', 'mailserver', 'mailport', 'mailtimeout', 'mailauth', 'mailfrom', 'mailauth_username', 'mailauth_password', 'maildelimiter', 'mailusername', 'mailsilent'];
		$updated = false;
		if($this->submitcheck()) {
			foreach($items as $item) {
				$value = getgpc($item, 'P');
				if($item == 'mailtimeout') {
					$value = strlen(trim($value)) ? intval($value) : 30;
				}
				$this->set_setting($item, $value);
			}
			$updated = true;
			$this->writelog('setting_mail_update');
			$this->updatecache();
		}

		$settings = $this->get_setting($this->_setting_items);
		if($updated) {
			$this->_add_note_for_setting($settings);
		}
		foreach($items as $item) {
			if($item == 'mailtimeout') {
				$settings[$item] = strlen(trim($settings[$item])) ? intval($settings[$item]) : 30;
			}
			$this->view->assign($item, dhtmlspecialchars($settings[$item]));
		}

		$this->view->assign('a', getgpc('a'));
		$this->view->assign('m', getgpc('m'));
		$this->view->assign('updated', $updated);
		$this->view->display('admin_setting');
	}

	private function _add_note_for_setting($settings) {
		$this->load('note');
		$_ENV['note']->add('updateclient', '', $this->serialize($settings, 1));
		$_ENV['note']->send();
	}

	private function _username_field($op, $new_length = '') {
		if($op == 'update') {
			$new_length = intval($new_length);
			if($new_length < 16 || $new_length > 255) {
				return false;
			}
		}
		$_ENV['showException'] = true;
		try {
			$fields = $this->db->query('DESC '.UC_DBTABLEPRE.'members');
		} catch (Exception $e) {
			$_ENV['showException'] = false;
			return false;
		}
		$_ENV['showException'] = false;
		foreach($fields as $field) {
			if($field['Field'] == 'username') {
				preg_match('/char\((\d+)\)/', $field['Type'], $r);
				if($r && !empty($r[1])) {
					if($op == 'update') {
						if($r[1] < $new_length) {
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'members CHANGE `username` `username` char('.$new_length.') NOT NULL DEFAULT \'\'');
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'protectedmembers CHANGE `username` `username` char('.$new_length.') NOT NULL DEFAULT \'\'');
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'protectedmembers CHANGE `admin` `admin` char('.$new_length.') NOT NULL DEFAULT \'\'');
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'admins CHANGE username username char('.$new_length.') NOT NULL DEFAULT \'\'');
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'feeds CHANGE `username` `username` char('.$new_length.') NOT NULL DEFAULT \'\'');
							$this->db->query('ALTER TABLE '.UC_DBTABLEPRE.'badwords CHANGE `admin` `admin` char('.$new_length.') NOT NULL DEFAULT \'\'');
							return true;
						}
					} else {
						return $r[1];
					}
				}
				break;
			}
		}
		return false;
	}
}