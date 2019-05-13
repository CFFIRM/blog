<?php
namespace app\index\model;

use think\Model;

class Admin extends Model
{
	protected $pk = 'admin_id';
	public function GetAdminInfo(){
		$list = Admin::paginate(10)->toArray();
		return $list;
	}
}