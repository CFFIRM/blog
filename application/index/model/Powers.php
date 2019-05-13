<?php
namespace app\index\model;

use think\Model;
use think\facade\Session;
use app\index\model\RolePower;

class Powers extends Model
{
	protected $pk = 'power_id';
	public function Getroleinfo(){
		$list = Powers::paginate(10)->toArray();
		return $list;
	}
	public function Delinfo(){
		$id=input('get.power_id');
		$res=Powers::where(['power_id'=>$id])->delete();
		return $res;
	}
	public function Delmore(){
		$id=input('get.power_id');

		$arr=explode(',',$id);
		foreach($arr as $k=>$v){
			$res=Powers::where(['power_id'=>$v])->delete();
		}
		return $res;
	}
	public function Getone(){
		$id=input('get.power_id');
		$res=Powers::where('power_id', $id)->find()->toArray();
		return $res;
	}
	public function InsUser(){
		$data=input('post.');
		$Powers= new Powers;
		$res=$Powers->save($data);
		return $res;
	}
	public function Updata(){
		$data=input('post.');
		$Powers= new Powers;
		$res=$Powers->save($data,['power_id'=>$data['power_id']]);
		return $res;
	}
	public function Getall(){
		$userinfo=Session::get('userinfo');
		$userinfo=json_decode($userinfo,true);
		$role_id=$userinfo['role_id'];
		$data=RolePower::where('role_id',$role_id)->field('power_id')->select()->toArray();
		$newarr=[];
		foreach($data as $k=>$v){
			$newarr[]=$v['power_id'];
		}
		$res=Powers::all($newarr)->toArray();
		return $res;
	}
	public function reserve(){
		echo 123;die;
		$data=input('post.');
		print_r($data);die;
	}
}