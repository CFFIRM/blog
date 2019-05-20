<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\Word as W;
use think\facade\Session;

class Word extends Controller
{
    public function showinfo(){
    	$meun=new Index();$meun->after();
        $info=W::paginate(10);

        // 获取分页显示
		$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view();
    }
    public function deleteword(){
		$id=input('get.id');
		$W = W::get($id);
		$res=$W->delete();
        if($res){
            $this->success("删除成功",'showinfo');
        }else{
            $this->error('发生了未知的错误');
        }
    }
    public function updateword(){
    	if(request()->ispost()){
            $id=input('post.id');
            $data['word_name']=input('post.word_name'); 
            $data['word_type']=input('post.word_type'); 
            $data['word_replace']=input('post.word_replace'); 
            $w=new W;
            $res=$w->save($data,['id'=>$id]);
            if($res){
                $this->success("修改成功",'showinfo');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
        	$id=input('get.id');
            $meun=new Index();$meun->after();
            $info=W::where('id',$id)->find();
            $this->assign('info',$info);
            return view();  
        }
    }
    public function addword(){
    	if(request()->ispost()){
    		$userinfo=Session::get('userinfo');
            $data=input('post.');
            $data['word_addtime']=time();
            $data['admin_id']=$userinfo['admin_id'];
            $data['admin_name']=$userinfo['admin_login_name'];
            $l=new W;
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
    public function otheraddword(){
    	if(request()->ispost()){
    		$userinfo=Session::get('userinfo');
    		$userinfo=json_decode($userinfo,true);
            $data=input('post.');
            $data['word_name']=strtr($data['word_name'],"\n",",");
            $data['word_addtime']=time();
            $data['admin_id']=$userinfo['admin_id'];
            $data['admin_name']=$userinfo['admin_login_name'];
            $l=new W;
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