<?php

// UC API 示例 PHP 语言

$r = new request();
$r->sample();

class request {

	public $url = 'http://127.0.0.1/uc_core/upload/api/';
	public $appid = '14664096';
	public $secret = 'E9JOFOFEQQSSZFLE';
	public $token = '';

	public function sample() {
		$ret = $this->_request('/token', []);
		if(!$ret || $ret['ret'] > 0 || empty($ret['token'])) {
			die('接口错误，无法获取 Token');
		}
		$this->token = $ret['token'];

		if(!empty($_GET['code'])) {
			$ret = $this->_request('/user/check_code', [
				'code' => $_GET['code'],
			]);
			print_r($ret);
			exit;
		}

		$ret = $this->_request('/user/login', [
			'username' => 'test',
			'password' => '1',
		]);
		if(!$ret || $ret['ret'] > 0) {
			die('接口错误，无法获取 /user/login');
		}
		print_r($ret);

		$ret = $this->_request('/user/get_user', [
			'username' => 'test',
		]);
		if(!$ret || $ret['ret'] > 0) {
			die('接口错误，无法获取 /user/get_user');
		}
		print_r($ret);

		/*
		$ret = $this->_request('/user/authorize', [
			'callback' => 'http://127.0.0.1/uc_core/developer/sample.php'
		]);
		print_r($ret);exit;
		*/

		$url = 'http://127.0.0.1/uc_core/upload?m=user&a=authorize&appid='.$this->appid.'&callback='.urlencode('http://127.0.0.1/uc_core/developer/sample.php');
		echo '<a href="'.$url.'">Authorize Login</a>';
	}

	private function _request($uri, $post) {
		$nonce = rand(1000, 2000);
		$t = time();
		$headers = [
			'appid' => $this->appid,
			'nonce' => $nonce,
			't' => $t,
			'sign' => base64_encode(hash('sha256', $nonce.$t.$this->secret)),
		];

		if($this->token) {
			$headers['token'] = $this->token;
		}

		$headersFmt = [];
		foreach($headers as $name => $value) {
			$canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
			$headersFmt[] = $canonicalName.': '.$value;
		}

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headersFmt,
			CURLOPT_URL => $this->url.'?'.$uri,
			CURLOPT_POST => 'POST',
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		]);
		$response = curl_exec($ch);
		echo $response;
		return json_decode($response, true);
	}

}
