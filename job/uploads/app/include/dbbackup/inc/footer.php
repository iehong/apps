<?php
$waitbaktime=(int)$_GET['waitbaktime'];
$stime=$_GET['stime'];
if(empty($stime)){
	$stime=time();
}
$t=$_GET['t'];
if(empty($t))
{$t=0;}
$p=$_GET['p'];
if(empty($p))
{$p=1;}
$btb=explode(",",$b_table);
$tbcount=count($btb);
//最后一个文件
if($p>=$tb[$btb[$t]]){
	$t++;
	//恢复完毕
	if($t>=$tbcount){

//        $errorMsg   =   "恢复成功！整个过程用时：".ToChangeUseTime($stime);
//        $result =   array('error' => 0, 'msg' => $errorMsg);
//        header('content-type:application/json; charset=utf-8');
//        echo json_encode($result);
//        exit;

		echo"成功<br/>";
		exit();
	}

	$nfile=$btb[$t]."_1.php";
	//进入下一个表
	//echo $fun_r['OneTableReSuccOne'].$btb[$t].$fun_r['OneTableReSuccTwo']."<script>self.location.href='$nfile?t=$t&p=0&mydbname=$mydbname&mypath=$mypath&stime=$stime';</script>";

	echo"<meta http-equiv=\"refresh\" content=\"".$waitbaktime.";url=$nfile?t=$t&p=0&mydbname=$mydbname&mypath=$mypath&stime=$stime&waitbaktime=$waitbaktime\">".$fun_r['OneTableReSuccOne'].$btb[$t-1].$fun_r['OneTableReSuccTwo'];
	exit();
}
//进入下一个文件
$p++;
$nfile=$btb[$t]."_".$p.".php";
//echo $fun_r['ReOneDataSuccess'].EchoBackupProcesserEchoReDataSt($btb[$t],$tbcount,$t,$tb[$btb[$t]],$p)."<script>self.location.href='$nfile?t=$t&p=$p&mydbname=$mydbname&mypath=$mypath&stime=$stime';</script>";

echo"<meta http-equiv=\"refresh\" content=\"".$waitbaktime.";url=$nfile?t=$t&p=$p&mydbname=$mydbname&mypath=$mypath&stime=$stime&waitbaktime=$waitbaktime\">".$fun_r['ReOneDataSuccess'];
$db->close();
$empire=null;
?>