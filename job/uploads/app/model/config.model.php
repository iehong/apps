<?php
/*
* $Author ：PHPYUN开发团队
*
* 官网: http://www.phpyun.com
*
* 版权所有 2009-2021 宿迁鑫潮信息技术有限公司，并保留所有权利。
*
* 软件声明：未经授权前提下，不得用于商业运营、二次开发以及任何形式的再次发布。
 */
class config_model extends model{
	
	/*
	 * 获取配置列表
	 * $whereData 	查询条件
	 * $data 		自定义处理数组
	 */
	 
	function getList($whereData = array(),$data=array()){
		$ListNew	=	array();
		$List		=	$this -> select_all('admin_config',$whereData,"`name`,`config`");
		
		if(!empty( $List )){
			
			$ListNew['list']	=	$List;
		}

		return	$ListNew;
	}
	
	/*
	* 获取配置详情
	* $whereData 	查询条件
	* $data 		自定义处理数组
	*
	*/
	

	function getInfo($whereData = array(), $data = array()){
		
		if($whereData){
			$data['field']  =	empty($data['field']) ? '*' : $data['field'];
		
			$List	=	$this -> select_once('admin_config',$whereData,$data['field']);
		}

		return $List;
	
	}

	/*
	* 创建配置
	* $setData 	自定义处理数组
	*
	*/
	

	function addInfo($setData){

		if(!empty($setData)){
			
			$nid	=	$this -> insert_into('admin_config',$setData);
			
		}

		return $nid;
	
	}

	/*
	* 更新配置
	* $whereData 	查询条件
	* $data 		自定义处理数组
	*
	*/

	function upInfo($whereData, $data = array()){

		if(!empty($whereData)){
			
			$nid	=	$this -> update_once('admin_config',$data,$whereData);
			
		}

		return $nid;
	
	}
	/*
	* 查询数量
	* $whereData 	查询条件
	*
	*/

	function getNum($whereData){

		if(!empty($whereData)){
			
			$num	=	$this -> select_num('admin_config',$whereData);
			
		}

		return $num;
	
	}
	
	//congfig参数设置
	function setConfig($data)
	{
	    $config  =  $this -> select_all('admin_config',array(),'`name`');
	    
	    foreach($config as $v){
			
			$allList[]	=	$v['name'];
		
		}
		
		foreach($data as $key=>$v){
			
			if(in_array($key,$allList)){
			    
				$this->upInfo(array('name'=>$key),array('config'=>$v));
				
			}else{
			    
				$this->addInfo(array('name'=>$key,'config'=>$v));
			
			}
		}
	}
	public function makeConfig(){
	    
	    $List		=	$this->getList(array('name'=>array('<>','')));
	    
	    $config		=	$List['list'];
	    
	    if(is_array($config)){
	        foreach($config as $v){
	            if($v['name']=='sy_member_icon_arr' || $v['name']=='sy_member_iconv_arr'){
	                $configarr[$v['name']]	=	empty($v['config']) ? '' : unserialize($v['config']);
	            }else{
	                $configarr[$v['name']]	=	$v['config'];
	            }
	        }
	    }
	    if(!empty($configarr)){
	        made_web(PLUS_PATH.'config.php',ArrayToString($configarr),'config');
	    }
	}
	/**
	 *  生成四位JS，css识别码 
	 */
	public function cacheCode(){
	    
	    $config     =  $this -> select_once('admin_config', array('name'=>'cachecode'));
	    
	    $cachecode  =  rand(1000, 9999);
	    
	    if (empty($config)) {
	        
	        $this->insert_into('admin_config',array('name'=>'cachecode','config'=>$cachecode));
	        
	    } else {
	        
	        $this->update_once('admin_config',array('config'=>$cachecode),array('name'=>'cachecode'));
	    }
	}
	/**
	 * 获取银行卡列表
	 * $whereData 	查询条件
	 * $data		自定义查询字段 field:查询字段，默认为*
	 */
	function getBankList($whereData=array(),$data=array('field'=>'*')){
		$list  =  $this -> select_all('bank',$whereData,$data['field']);
		return	$list;
	}
	/**
	 * 获取银行卡详细信息
	 * $whereData 	查询条件
	 * $data		自定义查询字段 field:查询字段，默认为*
	 */
	function getBankInfo($whereData=array(),$data=array('field'=>'*')){
		$BankInfo  =  $this -> select_once('bank',$whereData,$data['field']);
		return	$BankInfo;
	}
	/**
	 * 添加银行卡
	 * $whereData 	查询条件
	 * $data		自定义
	 */
	public function addBank($addData=array(),$data=array()){
		$return  =  $this -> insert_into('bank',$addData);
		return	$return;
	}
	/**
	 * 修改银行卡
	 * $whereData 	查询条件
	 * $data		自定义
	 */
	public function upBank($whereData=array(),$data=array()){
		$return  =  $this -> update_once('bank',$data,$whereData);
		return	$return;
	}
	/**
	 * 删除银行卡
	 * $whereData 	删除条件
	 * $data		自定义
	 */
	function delBank($whereData=array(),$data=array()){

		$result	=  $this -> delete_all('bank',$whereData,'');		
		return	$result;
	}
    /**
     * $type : 1-发布职位 2-简历下载 3-发布简历 4-充值 5-短信 6-投递 7-浏览简历 8-浏览职位 9-拨号
     */
    public $warnConfig = array(
        1 => '上架职位',
        2 => '下载简历',
        3 => '发布简历',
        4 => '充值额度',
        5 => '发送短信',
        6 => '申请职位',
        7 => '浏览简历',
        8 => '浏览职位',
        9 => '求职拨号',
        
        
        12 => '同一IP注册账号'
    );
	/**
	 * 获取预警设置列表
	 * $whereData 	查询条件
	 * $data		自定义查询字段 field:查询字段，默认为*
	 */
	public function getWarningList($whereData, $data=array())
    {
        if ($whereData) {

            $data['field']  =   empty($data['field']) ? '*' : $data['field'];

            $List   =   $this -> select_all('warning', $whereData, $data['field']);
            
            if (is_array($List)) {
                $warningId = array();
                $uid    =   array();
                $ruid = $cuid = $ltuid = array();
                foreach ($List as $v) {
                    $warningId[] = $v['id'];
                    $v['usertype'] == 1 && !in_array($v['uid'], $ruid) && array_push($ruid, $v['uid']);
                    $v['usertype'] == 2 && !in_array($v['uid'], $cuid) && array_push($cuid, $v['uid']);
                    $v['usertype'] == 3 && !in_array($v['uid'], $ltuid) && array_push($ltuid, $v['uid']);
                    $uid[]  =   $v['uid'];
                }
                if ($data['utype'] == 'admin') {
                    $this->update_once('warning', array('status' => 1), array('id' => array('in', pylode(',', $warningId))));
                }
                $member =   $this->select_all('member', array('uid' => array('in', pylode(',', $uid))), '`uid`,`username`');
                $nameArr = array();// 实名信息
                //个人信息
                if ($ruid) {
                    $resumeArr = $this->select_all('resume', array('uid' => array('in', pylode(',', $ruid))), '`uid`,`name`');
                    foreach ($resumeArr as $rv) {
                        $nameArr[$rv['uid']] = $rv['name'];
                    }
                }
                // 企业信息
                if ($cuid) {
                    $comArr = $this->select_all('company', array('uid' => array('in', pylode(',', $cuid))), '`uid`,`name`');
                    foreach ($comArr as $cv) {
                        $nameArr[$cv['uid']] = $cv['name'];
                    }
                }

                foreach ($List as $k => $v) {
                    $usertype_n = '';
                    if ($v['usertype'] == 1) {
                        $usertype_n = '个人用户';
                    } else if ($v['usertype'] == 2) {
                        $usertype_n = '企业用户';
                    }
                    $List[$k]['type_n'] =   $this->warnConfig[$v['type']];
                    $List[$k]['usertype_n'] = $usertype_n;
                    $List[$k]['name_n'] = $v['usertype'] && isset($nameArr[$v['uid']]) && $nameArr[$v['uid']] ? $nameArr[$v['uid']] : '';
                    
                    if($v['type']!=15){
                        $List[$k]['content'] = $List[$k]['type_n'].'超过预警设置';
                    }
                    $List[$k]['ctime_n'] = date('Y-m-d H:i:s',$v['ctime']);
                    
                    foreach ($member as $val) {
                        if ($v['uid'] == $val['uid']) {
                            $List[$k]['username'] = $val['username'];
                        }
                    }
                }
            }
        }
        return $List;
    }
	/**
    * @desc    删除预警设置列表
    */
	public function delWarning($delId)
    {
	    
        if (empty($delId)) {
            
            return array(
                'errcode'   => 8,
                'msg'       => '请选择要删除的数据！'
            );
            
        } else {
            $return     =   array();
            
            if (is_array($delId)) {
                
                $delId  =   pylode(',', $delId);
                $return['layertype']    =   1;
            } else {
            
                $return['layertype']    =   0;
            }

            $nid    =   $this -> delete_all('warning', array('id' => array('in', $delId)), '');
            
            if ($nid) {
            
                $return['msg']      =   '预警信息(ID:' . $delId . ')';
                $return['errcode']  =   $nid ? '9' : '8';
                $return['msg']      =   $nid ? $return['msg'] . '删除成功！' : $return['msg'] . '删除失败！';
            }
        }
        
        return $return;
    }
    //reg_congfig参数设置
    function setRegConfig($data)
    {
        $config  =  $this -> select_all('admin_reg_config',array(),'`name`');

        foreach($config as $v){

            $allList[]	=	$v['name'];

        }

        foreach($data as $key=>$v){

            if(in_array($key, $allList)){

                $this->upRegInfo(array('name' => $key), array('config'=> $v ? serialize($v) : ''));

            }else{

                $this->addRegInfo(array('name' => $key, 'config' => $v ? serialize($v) : ''));

            }
        }
    }

    /*
    * 创建配置
    * $setData 	自定义处理数组
    *
    */
    function addRegInfo($setData){

        if(!empty($setData)){

            $nid	=	$this -> insert_into('admin_reg_config',$setData);

        }

        return $nid;

    }

    /*
    * 更新配置
    * $whereData 	查询条件
    * $data 		自定义处理数组
    *
    */
    function upRegInfo($whereData, $data = array()){

        if(!empty($whereData)){

            $nid	=	$this -> update_once('admin_reg_config',$data,$whereData);

        }

        return $nid;

    }
}
?>