<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\Comment as C;

class Comment extends Controller
{
    public function commentlist()
    {
    	$meun=new Index();$meun->after();
        $info=C::paginate(10);
        // 获取分页显示
		$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view();
    }
    public function checkcomment(){
    	$a_id=input('post.comment_id');
        $user = C::where('comment_id',$a_id)->find();
        if($user['comment_status']==0){
            $user->comment_status     = 1;    
        }else{
            $user->comment_status     = 0;    
        }
        $user->save();
        $res = Wen::where('a_id',$a_id)->field('a_status')->find();
        echo json_encode($res);
    }
}