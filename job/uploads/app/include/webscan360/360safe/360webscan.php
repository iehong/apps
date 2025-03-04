<?php

webscan_error();
//引用配置文件
require_once('webscan_cache.php');
if (!defined('WEBSCAN_U_KEY')||WEBSCAN_U_KEY==null||WEBSCAN_U_KEY==''||WEBSCAN_U_KEY=="@webscan360key@"){
	
	//require_once dirname(dirname(__FILE__)).'/lib/webscan360_db.class.php';
	//$webscan360db = new Webscan360_db();
	//$ressult = $webscan360db->rec_getRow(array('var'=>'pkey'));

	//$webscan360db = new Webscan360_db();
	//$ressult = $webscan360db->rec_getRow(array('var'=>'pkey'));
	if(!empty($ressult)&&!empty($ressult['value'])){
		$pkey = $ressult['value'];
		define("WEBSCAN_U_KEY", "$pkey");
		define("WEBSCAN_API_LOG" , WEBSCAN_API_LOG_T . "/?key=".WEBSCAN_U_KEY);
		define("WEBSCAN_UPDATE_FILE" , WEBSCAN_UPDATE_FILE_T . "/?key=".WEBSCAN_U_KEY);
	}
}

//防护脚本版本号
define("WEBSCAN_VERSION", '0.1.1.9');
//防护脚本MD5值
define("WEBSCAN_MD5", md5(@file_get_contents(__FILE__)));
//get拦截规则
$getfilter = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
//post拦截规则
$postfilter = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
//cookie拦截规则
$cookiefilter = "\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
//获取指令
$webscan_action  = isset($_POST['webscan_act'])&&webscan_cheack() ? trim($_POST['webscan_act']) : '';
//referer获取
$webscan_referer = empty($_SERVER['HTTP_REFERER']) ? array() : array('HTTP_REFERER'=>$_SERVER['HTTP_REFERER']);

class webscan_http {

  var $method;
  var $post;
  var $header;
  var $ContentType;

  function __construct() {
    $this->method = '';
    $this->cookie = '';
    $this->post = '';
    $this->header = '';
    $this->errno = 0;
    $this->errstr = '';
  }

  function post($url, $data = array(), $referer = '', $limit = 0, $timeout = 30, $block = TRUE) {
    $this->method = 'POST';
    $this->ContentType = "Content-Type: application/x-www-form-urlencoded\r\n";
    if($data) {
      $post = '';
      foreach($data as $k=>$v) {
        $post .= $k.'='.rawurlencode($v).'&';
      }
      $this->post .= substr($post, 0, -1);
    }
    return $this->request($url, $referer, $limit, $timeout, $block);
  }

  function request($url, $referer = '', $limit = 0, $timeout = 30, $block = TRUE) {
    $matches = parse_url($url);
    $host = $matches['host'];
    $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
    $port = $matches['port'] ? $matches['port'] : 80;
    if($referer == '') $referer = URL;
    $out = "$this->method $path HTTP/1.1\r\n";
    $out .= "Accept: */*\r\n";
    $out .= "Referer: $referer\r\n";
    $out .= "Accept-Language: zh-cn\r\n";
    $out .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
    $out .= "Host: $host\r\n";
    if($this->method == 'POST') {
      $out .= $this->ContentType;
      $out .= "Content-Length: ".strlen($this->post)."\r\n";
      $out .= "Cache-Control: no-cache\r\n";
      $out .= "Connection: Close\r\n\r\n";
      $out .= $this->post;
    } else {
      $out .= "Connection: Close\r\n\r\n";
    }
    if($timeout > ini_get('max_execution_time')) @set_time_limit($timeout);
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $this->post = '';
    if(!$fp) {
      return false;
    } else {
      stream_set_blocking($fp, $block);
      stream_set_timeout($fp, $timeout);
      fwrite($fp, $out);
      $this->data = '';
      $status = stream_get_meta_data($fp);
      if(!$status['timed_out']) {
        $maxsize = min($limit, 1024000);
        if($maxsize == 0) $maxsize = 1024000;
        $start = false;
        while(!feof($fp)) {
          if($start) {
            $line = fread($fp, $maxsize);
            if(strlen($this->data) > $maxsize) break;
            $this->data .= $line;
          } else {
            $line = fgets($fp);
            $this->header .= $line;
            if($line == "\r\n" || $line == "\n") $start = true;
          }
        }
      }
      fclose($fp);
      return "200";
    }
  }

}

/**
 *   关闭用户错误提示
 */
function webscan_error() {
  if (ini_get('display_errors')) {
    ini_set('display_errors', '0');
  }
}

/**
 *  验证是否是官方发出的请求
 */
function webscan_cheack() {
  if($_POST['webscan_rkey']==WEBSCAN_U_KEY){
    return true;
  }
  return false;
}
/**
 *  数据统计回传
 */
function webscan_slog($logs) {
  if(WEBSCAN_U_KEY != "@webscan360key@" && WEBSCAN_U_KEY != '' && WEBSCAN_U_KEY != null){
	  if(! function_exists('curl_init')) {
		$http=new webscan_http();
		$http->post(WEBSCAN_API_LOG,$logs);
	  }
	  else{
		webscan_curl(WEBSCAN_API_LOG,$logs);
	  }
  }
}
/**
 *  参数拆分
 */
function webscan_arr_foreach($arr) {
  static $str;
  if (!is_array($arr)) {
    return $arr;
  }
  foreach ($arr as $key => $val ) {

    if (is_array($val)) {

      webscan_arr_foreach($val);
    } else {

      $str[] = $val;
    }
  }
  return implode($str);
}
/**
 *  新版文件md5值效验
 */
function webscan_updateck($ve) {
  if($ve!=WEBSCAN_MD5)
  {
    return true;
  }
  return false;
}

/**
 *  防护提示页
 */
function webscan_pape(){

  $pape=<<<HTML
  <html>
  <body style="margin:0; padding:0">
  <center><iframe width="100%" align="center" height="870" frameborder="0" scrolling="no" src="http://safe.webscan.360.cn/stopattack.html"></iframe></center>
  </body>
  </html>
HTML;
  echo $pape;
}

/**
 *  攻击检查拦截
 */
function webscan_StopAttack($StrFiltKey,$StrFiltValue,$ArrFiltReq,$method) {
  $StrFiltValue=webscan_arr_foreach($StrFiltValue);
  
  if (preg_match("/".$ArrFiltReq."/is",$StrFiltValue)==1){
    
    webscan_slog(array('ip' => $_SERVER["REMOTE_ADDR"],'time'=>strftime("%Y-%m-%d %H:%M:%S"),'page'=>$_SERVER["PHP_SELF"],'method'=>$method,'rkey'=>$StrFiltKey,'rdata'=>$StrFiltValue,'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'request_url'=>$_SERVER["REQUEST_URI"]));
    exit(webscan_pape());
  }
  if (preg_match("/".$ArrFiltReq."/is",$StrFiltKey)==1){
    webscan_slog(array('ip' => $_SERVER["REMOTE_ADDR"],'time'=>strftime("%Y-%m-%d %H:%M:%S"),'page'=>$_SERVER["PHP_SELF"],'method'=>$method,'rkey'=>$StrFiltKey,'rdata'=>$StrFiltKey,'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'request_url'=>$_SERVER["REQUEST_URI"]));
    exit(webscan_pape());
  }

}
/**
 *  拦截目录白名单
 */
function webscan_white($webscan_white_name,$webscan_white_url=array()) {
  $url_path=$_SERVER['PHP_SELF'];
  $url_var=$_SERVER['QUERY_STRING'];
  if (preg_match("/".$webscan_white_name."/is",$url_path)==1) {
    return false;
  }
  foreach ($webscan_white_url as $key => $value) {
    if(!empty($url_var)&&!empty($value)){
      if (stristr($url_path,$key)&&stristr($url_var,$value)) {
        return false;
      }
    }
    elseif (empty($url_var)&&empty($value)) {
      if (stristr($url_path,$key)) {
        return false;
      }
    }

  }

  return true;
}

/**
 *  curl方式提交
 */
function webscan_curl($url , $postdata = array()){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  $response = curl_exec($ch);
  $httpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close($ch);
  return array('httpcode'=>$httpcode,'response'=>$response);
}

if($webscan_action=='update') {
  //文件更新操作
  $webscan_update_md5=md5(@file_get_contents(WEBSCAN_UPDATE_FILE));
  if (webscan_updateck($webscan_update_md5))
  {
    if (!file_exists(dirname(__FILE__).'/caches_webscan'))
    {
      if (@mkdir(dirname(__FILE__).'/caches_webscan',755)) {
      }
      else{
        exit("file_failed");
      }
    }
    @file_put_contents(dirname(__FILE__).'/caches_webscan/'."update_360.dat", @file_get_contents(WEBSCAN_UPDATE_FILE));

    if(copy(__FILE__,dirname(__FILE__).'/caches_webscan/'."bak_360.dat")&&filesize(dirname(__FILE__).'/caches_webscan/'."update_360.dat")>500&&md5(@file_get_contents(dirname(__FILE__).'/caches_webscan/'."update_360.dat"))==$webscan_update_md5)
    {
      if (!copy(dirname(__FILE__).'/caches_webscan/'."update_360.dat",__FILE__))
      {
        copy(dirname(__FILE__).'/caches_webscan/'."bak_360.dat",__FILE__);
        exit("copy_failed");
      }
      unlink(dirname(__FILE__).'/caches_webscan/'."update_360.dat");
      exit("update_success");
    }
    unlink(dirname(__FILE__).'/caches_webscan/'."update_360.dat");
    exit("failed");
  }
  else{
    exit("news");
  }

}

elseif($webscan_action=="ckinstall") {
  //验证安装与版本信息
  if(! function_exists('curl_init')){
    $web_code=new webscan_http();
    $httpcode=$web_code->request("http://safe.webscan.360.cn");
  }
  else{
    $web_code=webscan_curl("http://safe.webscan.360.cn");
    $httpcode=$web_code['httpcode'];
  }

  exit("1".":".WEBSCAN_VERSION.":".WEBSCAN_MD5.":".WEBSCAN_U_KEY.":".$httpcode);
}


if ($webscan_switch&&webscan_white($webscan_white_directory,$webscan_white_url)) {
	
  if ($webscan_get) {

    foreach($_GET as $key=>$value) {
      webscan_StopAttack($key,$value,$getfilter,"GET");
    }
  }
  if ($webscan_post) {
    foreach($_POST as $key=>$value) {
      if($key!='sy_webtongji'){
        webscan_StopAttack($key,$value,$postfilter,"POST");
      }
      
    }
  }
  if ($webscan_cookie) {
    foreach($_COOKIE as $key=>$value) {
      webscan_StopAttack($key,$value,$cookiefilter,"COOKIE");
    }
  }
  if ($webscan_referre) {
    foreach($webscan_referer as $key=>$value) {
      webscan_StopAttack($key,$value,$postfilter,"REFERRER");
    }
  }
}

?>