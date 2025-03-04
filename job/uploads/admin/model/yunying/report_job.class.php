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

class report_job_controller extends adminCommon
{

    function index_action()
    {
        $reportM    =   $this->MODEL('report');

        $where      =   array();

        $ftypeStr   =   intval($_POST['ftype']);
        $keywordStr =   trim($_POST['keyword']);
        $where['type']      =   0;
        $where['usertype']  = 1;
        $reports    =   $this->obj->select_all('report', array('usertype' => 1, 'type' => 0), '`eid`,`p_uid`');

        $jobIds     =   $r_uids =   array();
        foreach ($reports as $rk => $rv) {
            $jobIds[$rv['eid']]     =   $rv['eid'];
            $r_uids[$rv['p_uid']]   =   $rv['p_uid'];
        }
        if ($ftypeStr == 1) {        //  职位名称
            $jobs           =   $this->obj->select_all('company_job', array('name' => array('like', $keywordStr), 'id' => array('in', pylode(',', $jobIds))), 'id');
            if (!empty($jobs)) {
                $eids       =   array();
                foreach ($jobs as $jk => $jv) {
                    $eids[] =   $jv['id'];
                }
            }
            $where['eid']   =   array('in', pylode(',', $eids));
        } elseif ($ftypeStr == 2) {   //  企业名称
            $where['r_name']=   array('like', $keywordStr);
        } elseif ($ftypeStr == 3) {   //  个人姓名
            $resumes        =   $this->obj->select_all('resume', array('uid' => array('in', pylode(',', $r_uids)), 'name' => array('like', $keywordStr)), 'uid');
            $uids           =   array();
            if (!empty($resumes)) {
                foreach ($resumes as $rek => $rev) {
                    $uids[] =   $rev['uid'];
                }
            }
            $where['p_uid'] =   array('in', pylode(',', $uids));
        }
        if(isset($_POST['status']) && $_POST['status']!==''){
            $where['status'] = $_POST['status'];
        }
        if ($_POST['order']) {
            $where['orderby'] = $_POST['t'] . ',' . $_POST['order'];
        } else {
            $where['orderby'] = 'id';
        }

        $pageM = $this->MODEL('page');

        $pages = $pageM->adminPageList('report', $where, $_POST['page'], array('limit' => $_POST['limit'], 'maxPage' => true));
        extract($pages);
        $limit = $pages['limit'][1];

        $list = [];
        if ($pages['total'] > 0) {
            $where['limit'] = $pages['limit'];
            $list = $reportM->getReportList($where, array('utype' => 'admin', 'type' => 0));
            $list = $list['list'];
        }
        $this->render_json(0, 'ok', compact('list', 'total', 'page_sizes', 'limit', 'page'));
    }

    function saveresult_action()
    {
        $reportM=   $this->MODEL('report');
        $id     =   intval($_POST['pid']);
        $result =   trim($_POST['result']);

        $upData =   array(
            'result'    =>  $result,
            'status'    =>  1,
            'rtime'     =>  time(),
            'admin'     =>  $_SESSION['auid']
        );

        $return =   $reportM->upReport(array('id' => $id), $upData);
        if ($return){
            $logM   =   $this->MODEL('log');
            $logM->addAdminLog("职位投诉(ID:" . $id . ")处理");
            $this->render_json(0, '操作成功');
        }else{
            $this->render_json(1, '操作失败，请重试');
        }
    }

    function del_action()
    {
        $reportM    =   $this->MODEL('report');
        $del = $_POST['del'];
        $where['id'] = $del;
        $return =   $reportM->delReport($where, array('title' => '举报'));

        $this->render_json($return['errcode']==9?0:1, $return['msg']);
    }

}

?>