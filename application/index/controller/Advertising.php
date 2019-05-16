<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\Advertising as A;

class Advertising extends Controller
{
    public function showinfo()
    {
    	$meun=new Index();$meun->after();
    	$info=A::where('is_delete',0)->paginate(10);
    	$page = $info->render();
		// 模板变量赋值
		$this->assign('info', $info);
		$this->assign('page', $page);
        return view('showinfo');
    }
    public function deleteadvert(){
		$id=input('get.id');
		$is_delete['is_delete']=1;
		$l=new A;
        $res=$l->save($is_delete,['id'=>$id]);
        if($res){
            $this->success("删除成功",'showinfo');
        }else{
            $this->error('发生了未知的错误');
        }
    }
    public function updateadvert(){
    	if(request()->ispost()){
            $id=input('post.id');
            $data['a_src']=input('post.a_src'); 
            $data['a_desc']=input('post.a_desc'); 
            $a=new A;
            $res=$a->save($data,$id);
            if($res){
                $this->success("修改成功",'showinfo');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
        	$id=input('get.id');
            $meun=new Index();$meun->after();
            $info=A::where('id',$id)->find()->toArray();
            $this->assign('info',$info);
            return view();    
        }
    }
    public function addadvert(){
    	if(request()->ispost()){
            $data=input('post.');
            $data['a_addtime']=time();
            $data['a_img']=$this->upload();
            $l=new A;
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
    public function upload(){
	    // 获取表单上传文件 例如上传了001.jpg
	    $file = request()->file('image');
	    // 移动到框架应用根目录/uploads/ 目录下
	    $info = $file->move( 'static/uploads/');
        $data="";
	    if($info){
	        // 成功上传后 获取上传信息
	        // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
	         $data=$info->getSaveName();
	    }else{
	        // 上传失败获取错误信息
	        
	    }
	    return $data;
	}
}