<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\Admin as Adminuser;
use app\index\model\Role;
use app\index\controller\Index;

class Admin extends Controller
{
    public function adminlist()
    {
    	$meun=new Index();$meun->after();
		// 查询状态为1的用户数据 并且每页显示10条数据
		$list = Adminuser::where('is_delete','0')->paginate(10);
		// 获取分页显示
		$page = $list->render();
		// 模板变量赋值
		$this->assign('info', $list);
		$this->assign('page', $page);
		// 渲染模板输出
		return $this->fetch();
    }
    public function deleteadmin(){
    	$a_id=input('get.id');
    	$user = Adminuser::get($a_id);
		$user->is_delete     = 1;
		$res=$user->save();
    	if($res){
    		$this->success('删除成功');
    	}else{
    		$this->error('删除失败');
    	}
    }
    public function addadmin(){
        if(request()->ispost()){
            $data=input('post.');
            if($data['admin_login_pwd']==$data['again_pwd']){
                $data['admin_updatetime']=time();
                $data['admin_login_pwd']=md5($data['admin_login_pwd']);
                $user = new Adminuser;
                $res=$user->save($data);
                if($res){
                    $this->success("添加成功",'adminlist');
                }else{
                    $this->error('发生了未知的错误');
                }    
            }else{
                $this->error('两次密码输入不一致');
            }
            
        }else{
            $meun=new Index();$meun->after();
            $role=Role::select()->toArray();
            return view('addadmin',['data'=>$role]);
        }
    }
    public function checkname(){
        $data=input('post.');
        $arr=array_keys($data);
        if($arr[0]=='name'){
            $res=Adminuser::where('admin_login_name',$data['name'])->field('admin_id')->find();
        }else if($arr[0]=='email'){
            $res=Adminuser::where('admin_email',$data['email'])->field('admin_id')->find();
        }else if($arr[0]=='tel'){
            $res=Adminuser::where('admin_tel',$data['tel'])->field('admin_id')->find();
        }
        if($res){
            echo json_encode(['errcode'=>1]);
        }else{
            echo json_encode(['errcode'=>0]);
        }
    }
}