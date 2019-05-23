<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\User as U;

class User extends Controller
{
    public function userlist()
    {
    	$meun=new Index();$meun->after();
        $info=U::paginate(10);

        // 获取分页显示
		$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view();
    }
    public function checkuser(){
    	$id=input('post.id');
        $user = U::where('id',$id)->find();
        $status=input('post.status');
        if($user['user_status']==$status){
        	echo json_encode(['errcode'=>1]);//代表状态相同，无须更改
        	return false;
        }else if($status==0){
            $user->user_status = 0;
            $user->user_disabletime=null;
        }else if($status==1){
            $user->user_status = 1;
            $user->user_disabletime=time()+(7*86400);
        }else if($status==2){
        	$user->user_status = 2;
            $user->user_disabletime=null;
        }
        $user->save();
        $res = U::where('id',$id)->field('id,user_status,user_disabletime')->find();
        $res['user_disabletime']=date('Y-m-d H:i:s',$res['user_disabletime']);
        echo json_encode($res);
    }
}