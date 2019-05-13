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
		$this->assign('list', $list);
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
    	$meun=new Index();$meun->after();
    	$role=Role::select()->toArray();
    	return view('addadmin',['data'=>$role]);
    }
}