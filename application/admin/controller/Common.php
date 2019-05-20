<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\facade\Session;
use app\admin\model\RolePower;
use app\admin\model\Powers;
use think\facade\Request;
class Common extends Controller 
{
	 protected function initialize(){
	 	$userinfo=Session::get('userinfo');
	 	if(empty($userinfo)){
	 		$this->error('您还未登录，将返回至登录界面','index/index');
	 	}else{
	 		$this->check_power();
	 	}
	 }
	 public function check_power(){
	 	$userinfo=Session::get('userinfo');
		$userinfo=json_decode($userinfo,true);
		$role_id=$userinfo['role_id'];
		$data=RolePower::where('role_id',$role_id)->field('power_id')->select()->toArray();
		$newarr=[];
		foreach($data as $k=>$v){
			$newarr[]=$v['power_id'];
		}
		$res=Powers::all($newarr)->toArray();
		$controller=Request::controller();
		$function=Request::action();
		$flag=false;
		foreach($res as $k=>$v){
			if($controller==$v['controller']){
				if($function==$v['function']){
					$flag=true;
				}
			}
		}
		$f_id=input('get.f_id');
		if(!isset($f_id)){
			$f_id=$this->request->param('a_fid');
		}
		if(!$flag){
			$this->error('抱歉，您没有此权限');
		}
	 }
}