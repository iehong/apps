<?php
class SendSMS
{
    public static $clients = array ();

    
    public static function getSecodeMessage($code)
    {
        global $_G;
        $template = "您的验证码是：【变量】";
		if (isset($_G['setting']['login_mobile_smsset'])){
			$setting = unserialize($_G['setting']['login_mobile_smsset']);
            if (isset($setting["template2"]) && $setting["template2"]!="") {
                $template = iconv(CHARSET, "UTF-8//ignore", $setting["template2"]);
            }
        }
        $msg = preg_replace("/【变量】/i", $code, $template);
        return $msg;
    }

    public static function send($appid,$appkey,$phone,$msg,$smsid=1)
    {
        $res = self::send_huyi($appid,$appkey,$phone,$msg);
		if ($res["retcode"]==0) {
			C::t("#login_mobile#mobile_login_sms")->save($phone,$msg);
        }
        return $res;
    }

    private static function send_huyi($appid,$appkey,$phone,$msg)
    {
        require_once dirname(__FILE__)."/smsclient/sendmsg_huyi.class.php";
        return SendSMS_Huyi::send($appid,$appkey,$phone,$msg);
    }
}
?>
