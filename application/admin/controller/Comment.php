<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\Comment as C;

class Comment extends Controller
{
    public function commentlist()
    {
    	$meun=new Index();$meun->after();
        $info=C::where('comment_status',1)->paginate(10);
        // 获取分页显示
		$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view('commentlist');
    }
    public function commentlist_shen()
    {
        $meun=new Index();$meun->after();
        $info=C::where('comment_status',0)->paginate(10);
        // 获取分页显示
        $page = $info->render();
        // 模板变量赋值
        $this->assign('info', $info);
        $this->assign('page', $page);
        return view('commentlist');
    }
    public function commentlist_pass()
    {
        $meun=new Index();$meun->after();
        $info=C::where('comment_status',2)->paginate(10);
        // 获取分页显示
        $page = $info->render();
        // 模板变量赋值
        $this->assign('info', $info);
        $this->assign('page', $page);
        return view('commentlist');
    }
    public function checkcomment(){
        $comment_id=input('post.comment_id');
    	$comment_status=input('post.comment_status');
        $user = C::where('comment_id',$comment_id)->find();
        $user->comment_status=$comment_status;
        $user->save();
        $res = C::where('comment_id',$comment_id)->field('comment_status')->find();
        echo json_encode($res);
    }
}