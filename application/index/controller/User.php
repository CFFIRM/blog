<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\User as U;

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
        	echo json_encode(['errcode'=>1]);//代表 状态相同，无须更改
        	return false;
        }else if($status==0){
            $user->user_status = 0;    
        }else if($status==1){
            $user->user_status = 1;    
        }else if($status==2){
        	$user->user_status = 2;
        }
        $user->save();
        $res = U::where('id',$id)->field('user_status','id')->find();
        echo json_encode($res);
    }
}