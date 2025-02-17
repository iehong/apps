<?php

class login_mobile_bksvr
{
    
    public static function get_sms_info()
    {
		$info = array(
            "list" => array("本插件当前已经集成了多个短信平台客户端供您选择。"),
            "default_sms" => 1,
            "clients"=>array(
				array("text"=>"互亿无线","value"=>"1","desc"=>"您可以前往123<a href=\\\"http://www.ihuyi.com/\\\" target=\\\"_blank\\\">互亿无线官网</a>申请账号"),
            )
        );
        try {
			$url = "http://139.196.29.35/login_mobile/index.php/api/index?version=2.3.0";
			$res = self::http_request($url);
			if (isset($res["list"])) $info["list"] = $res["list"];
			if (isset($res["default_sms"])) $info["default_sms"] = intval($res["default_sms"]);
			if (isset($res["clients"])) $info["clients"] = $res["clients"];
        } catch (Exception $e) {
            runlog("login_mobile", "get_sms_info fail: ".$e->getMessage());
        }
        return $info;
    }

    
    private static function http_request($url, $postData=null)
    {
        $data = "";
        $urlarr = array($url);
        foreach ($urlarr as $k => $ithurl) {
			$ch = curl_init();
            if ($k!=0 && $domain!="") {
				$header = array ("Host: $domain");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			} 
			if(!is_null($postData)){
				$curlPost = http_build_query($postData);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			}
			curl_setopt($ch, CURLOPT_URL, $ithurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
			$data      = curl_exec($ch);
			$errorInfo = curl_error($ch);
            $httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if($httpCode!=200 || !empty($errorInfo)){
				curl_close($ch);
                continue;
			}
			if(empty($data) && empty($postData)){
				curl_close($ch);
                break;
			}
			curl_close($ch);
		}
        if ($data == "") {
			$tmp = file_get_contents($url);
		    if(!empty($tmp)){
			    $data = $tmp;
		    }
        }
        $res = json_decode($data,true);
        if (empty($res)) {
            throw new Exception("http_request_fail [res:$data]");
        }
        return $res;
    }
}
?>
