<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use thinK\Session;
use app\index\model\Role;
use app\index\controller\Index;
use app\index\controller\Common;
class Layouts extends Common
{
    public function normal()
    {
        $meun=new Index();$meun->after();
        return view();
    }
    public function role_list(){
        $meun=new Index();
        $meun->after();
    	$role=new Role();
    	$res=$role->Getroleinfo();
    	$this->assign('page',$res);
    	return view();
    }
    public function addrole(){
        if(request()->ispost()){
            $Role=new Role();
            $res=$Role->InsUser();
            if($res){
                $this->redirect('role_list');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $meun=new Index();$meun->after();
            return view();    
        }
        
    }
	public function delete(){
		$Role=new Role();
    	$res=$Role->Delinfo();

    	if($res){
    		$this->success('删除成功');
    	}else{
    		$this->error('删除失败');
    	}
	}
	public function compile(){
        if(request()->ispost()){
            $Role=new Role();
            $res=$Role->Updata();
            if($res==1){
                $this->redirect('role_list');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $meun=new Index();$meun->after();
            $Role=new Role();
            $res=$Role->Getone();
            return view('compile',['data'=>$res]);    
        }   
    }
}
