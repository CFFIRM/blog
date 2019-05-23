<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\Link as L;

class Link extends Controller
{
    public function showinfo()
    {
    	$meun=new Index();$meun->after();
    	$info=L::where('is_delete',0)->paginate(10);
    	$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view('showinfo');
    }
    public function deletelink(){
		$l_id=input('get.l_id');
		$is_delete['is_delete']=1;
		$l=new L;
        $res=$l->save($is_delete,['l_id'=>$l_id]);
        if($res){
            $this->success("删除成功",'showinfo');
        }else{
            $this->error('发生了未知的错误');
        }
    }
    public function updatelink(){
    	if(request()->ispost()){
            $l_id=input('post.l_id');
            $data['l_name']=input('post.l_name');
            $data['l_src']=input('post.l_src'); 
            $l=new L;
            $res=$l->save($data,['l_id'=>$l_id]);
            if($res){
                $this->success("修改成功",'showinfo');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
        	$l_id=input('get.l_id');
            $meun=new Index();$meun->after();
            $info=L::where('l_id',$l_id)->find();
            $this->assign('info',$info);
            return view();    
        }
    }
    public function addlink(){
    	if(request()->ispost()){
            $data=input('post.');
            $data['l_addtime']=time();
            $l=new L;
            $res=$l->save($data);
            if($res){
                $this->success("添加成功",'showinfo');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $meun=new Index();$meun->after();
            return view();    
        }
    }
}