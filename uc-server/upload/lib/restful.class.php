<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

!defined('IN_UC') && exit('Access Denied');

const Error = [
	-101 => 'checkSign: param is missing',
	-102 => 'checkSign: appid is invalid',
	-103 => 'checkSign: sign is expire',
	-104 => 'checkSign: sign is invalid',
	-105 => 'checkToken: token is missing',
	-106 => 'checkToken: token is expire',
	-107 => 'checkToken: appid is error',
	-108 => 'getTokenData: token is error',
	-109 => 'getApiParam: api is invalid',
	-110 => 'getAppidPerm: appid is invalid',
	-111 => 'getSecret: appid is invalid',
	-112 => 'parseQuery: api url is empty',
	-113 => 'parseQuery: api url is error',
	-114 => 'initParam: api is invalid',
	-115 => 'apiPermCheck: api is invalid',
	-116 => 'apiFreqCheck: out of frequency',
	-117 => 'scriptCheck: script is empty',
	-118 => 'scriptCheck: script format is error',
	-119 => 'callback: authtoken is invalid',
	-200 => 'missing redis',
	-201 => 'invalid request',
	-202 => 'callback is invalid',
];

class restful {

	public $tokenData;
	const SignTTL = 300;
	const TokenTTL = 1800;
	const TokenSaveTTL = 3600;
	const TokenPre = 'rToken_';
	const AppIdConfPre = 'rApp_';
	const ApiFreqPre = 'rFreq_';
	const ApiFreqTTL = 60;
	const ApiAuthorize = 'rAuth_';
	const ApiAuthorizeTTL = 300;

	public function error($code) {
		if(defined('RESTFUL_OUTPUT') && RESTFUL_OUTPUT == 'html') {
			echo $code;
			if(!empty(Error[$code])) {
				echo ' '.Error[$code];
			}
			exit;
		}
		output($code);
	}

	public function output($value) {
		echo json_encode($value);
		exit;
	}

	private function _memory($method, $params = []) {
		if(!method_exists($_ENV['redis'], $method)) {
			return null;
		}
		return call_user_func_array([$_ENV['redis'], $method], $params);
	}

	public function checkSign() {
		if(empty($_SERVER['HTTP_APPID']) ||
			empty($_SERVER['HTTP_SIGN']) ||
			empty($_SERVER['HTTP_NONCE']) ||
			empty($_SERVER['HTTP_T'])) {
			output(-101);
		}
		$this->_getAppidPerm($_SERVER['HTTP_APPID']);
		$secret = $this->_getSecret();
		if(!$secret) {
			$this->error(-102);
		}
		if(time() - $_SERVER['HTTP_T'] > self::SignTTL) {
			$this->error(-103);
		}
		if($_SERVER['HTTP_SIGN'] != base64_encode(hash('sha256', $_SERVER['HTTP_NONCE'].$_SERVER['HTTP_T'].$secret))) {
			$this->error(-104);
		}
	}

	public function token() {
		$token = strtoupper($this->random(16));
		$tokenData = $this->isRefreshToken() ?
			$this->refreshTokenData() :
			$this->newTokenData();
		$this->setToken($token, $tokenData);
		if($this->isRefreshToken()) {
			$this->delTokenData();
		}
		$this->output([
			'ret' => 0,
			'token' => $token,
			'expires_in' => time() + self::TokenTTL,
		]);
	}

	public function checkToken() {
		if(empty($this->_getToken())) {
			$this->error(-105);
		}
		$v = $this->_getTokenData();
		if(time() >= $v['exptime']) {
			$this->error(-106);
		}
		$this->tokenData = $v;
		if(!$v['_appid'] || $v['_appid'] != $_SERVER['HTTP_APPID']) {
			$this->error(-107);
		}
		$this->_getAppidPerm($v['_appid']);
		$this->token = $this->_getToken();
	}

	public function getToken() {
		$this->_getAppidPerm($_REQUEST['appid'] ?? 0);
		$this->token = $this->_getToken();
	}

	public function setToken($key, $value, $ttl = self::TokenSaveTTL) {
		$this->_memory('set', [self::TokenPre.$key, json_encode($value), $ttl]);
	}

	public function isRefreshToken() {
		return !empty($this->_getToken());
	}

	private function _getToken() {
		return !empty($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : (!empty($_POST['token']) ? $_POST['token'] : '');
	}

	private function _getTokenData() {
		$v = json_decode($this->_memory('get', [self::TokenPre.$this->_getToken()]), true);
		if(empty($v)) {
			$this->error(-108);
		}
		return $v;
	}

	public function refreshTokenData() {
		$v = $this->_getTokenData();
		$data['_appid'] = $_SERVER['HTTP_APPID'];
		$data['_conf'] = $this->tokenData['_conf'];
		$data['exptime'] = time() + self::TokenTTL;
		$data['refreshExptime'] = time() + self::TokenSaveTTL;
		return json_encode($data);
	}

	public function apiPermCheck($api) {
		if(empty($this->tokenData['_conf']['apis'][$api])) {
			$this->error(-114);
		}
	}

	public function apiFreqCheck($api) {
		if(!empty($this->tokenData['_conf']['freq'][$api])) {
			$key = self::ApiFreqPre.$this->tokenData['_appid'].'_'.$api;
			$v = $this->_memory('get', [$key]);
			if(!$v) {
				$this->_memory('inc', [$key]);
				$this->_memory('expire', [$key, self::ApiFreqTTL]);
			} elseif($v >= $this->tokenData['_conf']['freq'][$api]) {
				$this->error(-116);
			} else {
				$this->_memory('inc', [$key]);
			}
		}
	}

	public function delTokenData() {
		$this->_memory('rm', [self::TokenPre.$this->_getToken()]);
	}

	public function newTokenData() {
		$data = [];
		$data['_appid'] = $_SERVER['HTTP_APPID'];
		$data['_conf'] = $this->tokenData['_conf'];
		$data['exptime'] = time() + self::TokenTTL;
		$data['refreshExptime'] = time() + self::TokenSaveTTL;
		return json_encode($data);
	}


	private function _getAppidPerm($appid) {
		$v = $this->_memory('get', [self::AppIdConfPre.$appid]);
		if(!$v) {
			$this->error(-110);
		}
		$this->tokenData['_conf'] = $v;
	}

	public function setAppId($appid, $param) {
		$this->_memory('set', [self::AppIdConfPre.$appid, $param]);
	}

	public function rmAppId($appid) {
		$this->_memory('rm', [self::AppIdConfPre.$appid]);
	}

	public function setAuthorize($uid) {
		$key = $this->random(10);
		$this->_memory('set', [self::ApiAuthorize.$key, $uid]);
		$this->_memory('expire', [self::ApiAuthorize.$key, self::ApiAuthorizeTTL]);
		return $key;
	}

	public function getAuthorize($key) {
		return $this->_memory('get', [self::ApiAuthorize.$key]);
	}

	public function rmAuthorize($key) {
		return $this->_memory('rm', [self::ApiAuthorize.$key]);
	}

	private function _getSecret() {
		if(empty($this->tokenData['_conf']['secret'])) {
			$this->error(-111);
		}
		return $this->tokenData['_conf']['secret'];
	}

	public function random($length, $numeric = 0) {
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

}

