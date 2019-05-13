<?php
namespace app\index\model;

use think\Model;

class Role extends Model
{
	public function Getroleinfo(){
		$list = Role::paginate(5);
		return $list;
	}
	public function Delinfo(){
		$id=input('get.role_id');
		$res=Role::where(['role_id'=>$id])->delete();
		return $res;
	}
	public function Delmore(){
		$id=input('get.role_id');

		$arr=explode(',',$id);
		foreach($arr as $k=>$v){
			$res=Role::where(['role_id'=>$v])->delete();
		}
		return $res;
	}
	public function Getone(){
		$id=input('get.role_id');
		$res=Role::where('role_id', $id)->find()->toArray();
		return $res;
	}
	public function InsUser(){
		$data=input('post.');
		$Role= new Role;
		$res=$Role->save($data);
		return $res;
	}
	public function Updata(){
		$data=input('post.');
		$Role= new Role;
		$res=$Role->save($data,['role_id'=>$data['role_id']]);
		return $res;
	}
	public function Getall(){
		$res=Role::select()->toArray();
		return $res;
	}
}