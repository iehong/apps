<?php

if(empty($_POST) && $_body = file_get_contents('php://input')) {
	$_POST = json_decode($_body, true);
}
const IN_RESTFUL_API = 1;

const Error_Redis_Missing = -999;

$s = rawurldecode($_SERVER['QUERY_STRING']);

if(substr($s, -1) == '=') {
	$s = substr($s, 0, -1);
}

if(substr($s, 0, 1) != '/') {
	$s = '/'.$s;
}
$e = explode('/', $s);

$c = count($e);
if($c == 2) {
	if($e[1] == 'token') {
		$_POST['m'] = $_REQUEST['m'] = 'user';
		$_POST['a'] = $_REQUEST['a'] = 'restful';
		define('RESTFUL_ACTION', $e[1]);
	}
} else {
	if($c < 3) {
		output(-201);
	}

	$_POST['m'] = $_REQUEST['m'] = !empty($e[1]) ? $e[1] : '';
	$_POST['a'] = $_REQUEST['a'] = !empty($e[2]) ? $e[2] : '';
	$_POST['appid'] = $_REQUEST['appid'] = !empty($e[3]) ? $e[3] : '';
}

register_shutdown_function(function() {
	$s = ob_get_contents();
	if(is_numeric($s)) {
		ob_end_clean();
		output($s);
	}
});

require_once '../index.php';

function output($code) {
	$return = [
		'ret' => $code,
	];
	if(!empty(Error[$code])) {
		$return['msg'] = Error[$code];
	}
	echo json_encode($return);
	exit;
}
