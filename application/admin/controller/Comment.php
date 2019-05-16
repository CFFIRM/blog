<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\Comment as C;

class Comment extends Controller
{
    public function addcomment(){
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
    	$data=input('post.');
    	$data['comment_addtime']=time();
        $data['user_id']=$bg_userinfo['id'];
    	$c=new C;
    	$res=$c->save($data);
    	if($res){
    		echo json_encode(['errcode'=>1,'msg'=>"评论成功，待审核"]);
    	}else{
    		echo json_encode(['errcode'=>2,'msg'=>"评论失败"]);
    	}
    }	
}