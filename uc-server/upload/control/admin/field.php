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
		if(!$this->user['isfounder'] && !$this->user['allowadminsetting']) {
			$this->message('no_permission_for_this_module');
		}

		$settings = $this->get_setting('fields', true);
		$this->fields = $settings['fields'];
		$this->load('user');
		$this->load('cache');
	}

	private function _alter($sql) {
		try {
			$_ENV['user']->alter($sql);
		} catch (Exception $e) {
			$this->message($e->getMessage(), 'BACK');
		}
	}

	public function onls() {
		if($this->submitcheck()) {
			if(!empty($_POST['delete'])) {
				foreach($_POST['delete'] as $field) {
					$this->_alter('DROP COLUMN '.$field);
					unset($this->fields[$field]);
				}
			}
			foreach($this->fields as $field) {
				if(empty($field['index']) && !empty($_POST['index'][$field['field']])) {
					$this->_alter('ADD INDEX '.$field['field'].'('.$field['field'].')');
				}
				if(!empty($field['index']) && empty($_POST['index'][$field['field']])) {
					$this->_alter('DROP INDEX '.$field['field']);
				}
				if(empty($field['unique']) && !empty($_POST['unique'][$field['field']])) {
					$this->_alter('ADD UNIQUE '.$field['field'].'('.$field['field'].')');
				}
				if(!empty($field['unique']) && empty($_POST['unique'][$field['field']])) {
					$this->_alter('DROP UNIQUE '.$field['field']);
				}
				$this->fields[$field['field']]['index'] = !empty($_POST['index'][$field['field']]);
				$this->fields[$field['field']]['unique'] = !empty($_POST['unique'][$field['field']]);
			}
			$this->set_setting('fields', $this->fields, true);
			$_ENV['cache']->updatedata('fields');
		}

		$a = getgpc('a');
		$this->view->assign('a', $a);
		$this->view->assign('fields', $this->fields);
		$this->view->display('admin_field');
	}

	public function onadd() {
		if($this->submitcheck()) {
			$s = getgpc('s');
			if(isset($this->fields[$s['field']])) {
				$this->message('字段名重复', 'BACK');
			}
			if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $s['field'])) {
				$this->message('字段名不合法', 'BACK');
			}
			if(!empty($s['len'])) {
				switch($s['type']) {
					case 'int':
						$s['len'] = intval($s['len'] > 0 && $s['len'] < 11 ? $s['len'] : 10);
						break;
					case 'double':
						list($m, $d) = explode($s['len'], ',');
						$m = intval($m > 0 && $m < 11 ? $m : 6);
						$d = intval($d > 0 && $d < 6 ? $d : 3);
						$s['len'] = $m.','.$d;
						break;
					case 'varchar':
						$s['len'] = intval($s['len'] > 0 && $s['len'] < 255 ? $s['len'] : 255);
						break;
					case 'text':
						$s['len'] = '';
						break;
				}
			} else {
				$s['len'] = '';
			}
			$unsigned = !empty($s['unsigned']) ? ' unsigned' : '';
			$this->_alter('ADD COLUMN '.$s['field'].' '.$s['type'].($s['len'] ? '('.$s['len'].')' : '').$unsigned);
			if(!empty($s['unique'])) {
				$this->_alter('ADD UNIQUE '.$s['field'].'('.$s['field'].')');
			} elseif(!empty($s['index'])) {
				$this->_alter('ADD INDEX '.$s['field'].'('.$s['field'].')');
			}

			$this->fields[$s['field']] = [
				'name' => $s['name'],
				'field' => $s['field'],
				'type' => $s['type'].'('.$s['len'].') '.$unsigned,
				'index' => !empty($s['index']),
				'unique' => !empty($s['unique']),
			];
			$this->set_setting('fields', $this->fields, true);
			$_ENV['cache']->updatedata('fields');
			header('location: '.UC_ADMINSCRIPT."?m=field&a=ls&sid=".$this->view->sid);
		}
		$a = getgpc('a');
		$this->view->assign('a', $a);
		$this->view->display('admin_field');
	}

}

