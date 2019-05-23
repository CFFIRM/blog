<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\facade\Request;
use think\facade\App;
use app\admin\model\User;
use app\admin\model\Role;
use app\admin\model\Powers;
use app\admin\model\RolePower;
use app\admin\model\Cat;
use app\admin\controller\Index;
use app\admin\controller\Common;

class Power extends Common
{   
	public function user_list(){
        $meun=new Index();$meun->after();
		$User=new User();
    	$res=$User->Getuserinfo();
    	return view('user_list',['page'=>$res]);
	}
	public function adduser(){
        if(request()->ispost()){
            $User=new User();
            $res=$User->InsUser();
            if($res){
                $this->redirect('user_list');
            }else{
                $this->error('发生了未知的错误');
            }    
        }else{
            $meun=new Index();$meun->after();
            $role=Role::select()->toArray();
            $this->assign('role',$role);
            return view();    
        }
        
	}
	public function delete(){
		$Asdf=new User();
    	$res=$Asdf->Delinfo();
    	if($res){
    		$this->success('删除成功');
    	}else{
    		$this->error('f_list');
    	}
	}
	public function compile(){
        $User=new User();
        if(request()->ispost()){
            $res=$User->Updata();
            if($res){
                $this->redirect('user_list');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $meun=new Index();$meun->after();
            $role=Role::select()->toArray();
            $res=$User->Getone();
            $this->assign('role',$role);
            return view('compile',['data'=>$res]);
        }
    }
    public function poweradd(){
        if(request()->ispost()){
            $data=input('post.');
            $res=$this->Code($data);
            $Power = new RolePower;
            $list = RolePower::where('role_id',$data['role_id'])->delete();
            $result=$Power->saveAll($res);
            if($result){
                echo json_encode(['code'=>1,'msg'=>"添加成功"]);
            }else{
                echo json_encode(['code'=>2,'msg'=>"添加失败"]);
            }
        }else{
            $meun=new Index();
            $meun->after();
            $role=new Role();
            $roleinfo=$role->Getall();
            $powerinfo=Powers::select()->toArray();
            $res=$this->tree($powerinfo);
            $this->assign('roleinfo',$roleinfo);
            $this->assign('powerinfo',$res);
            return view();    
        }
        
    }
    public function tree($power,$level=1,$f_id=0){
        static $arr=[];
        foreach($power as $k=>$v){
            if($v['f_id']==$f_id){
                $v['level']=$level;
                $arr[]=$v;
                $this->tree($power,$level+1,$v['power_id']);
            }
        }
        return $arr;
    }
    public function Code($arr){
        $ids=array_unique(explode(',',$arr['ids']));
        static $newarr=[];
        foreach($ids as $k=>$v){
            $newarr[$k]['power_id']=$v;
            $newarr[$k]['role_id']=$arr['role_id'];
        }
        return $newarr;
    }
    public function meunmanage(){
        $meun=new Index();
        $meun->after();
        $page=Powers::paginate(10);
        $this->assign('page',$page);
        return $this->fetch();
    }
    public function meunadd(){
        if(request()->ispost()){
            $power=new Powers();
            $data=input('post.');
            if(!isset($data['f_id'])){
                $arr['power_name']=$data['power_name'];
                $arr['f_id']=0;
                $res=$power->save($arr);
                if($res){
                    return $this->success('添加菜单成功','power/meunmanage');
                }else{
                    return $this->error('添加菜单失败');
                }
            }else{
                $arr['power_name']=$data['power_name'];
                $arr['f_id']=$data['f_id'];
                $arr['is_show']=$data['is_show'];
                $arr['controller']=$data['controller'];
                $arr['function']=$data['function'];
                $res=$power->save($arr);
                if($res){
                    return $this->success('添加菜单成功','power/meunmanage');
                }else{
                    return $this->error('添加菜单失败');
                }
            }
        }else{
            $meun=new Index();
            $meun->after();
            $controller=$this->redController();
            $this->assign('controller',$controller);
            return view();    
        }
    }
    public function erji(){
        $data=Powers::order('power_id', 'asc')->select()->toArray();
        $data=$this->tree($data);
        echo json_encode($data);
    }
    public function deletemeun(){//删除菜单
        $id=input('get.power_id');
        $res=Powers::where(['power_id'=>$id])->delete();
        if(Powers::where('f_id',$id)->select()->toArray()){
            $res=Powers::where(['f_id'=>$id])->delete();
        }
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    public function compilemeun(){//修改菜单页面渲染
        if(request()->ispost()){
            $Powers=new Powers();
            $res=$Powers->Updata();
            if($res){
                $this->redirect('meunmanage');
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $meun=new Index();$meun->after();
            $Powers=new Powers();
            $res=$Powers->Getone();
            return view('compilemeun',['data'=>$res]);    
        }
        
    }
    private function redController(){//获取当前控制器目录下所有的文件名
        $arr=scandir('./../application/admin/controller');
        unset($arr[0],$arr[1]);
        $list=[];
        foreach($arr as $k=>$v){
            $list[]=pathinfo($v,PATHINFO_FILENAME);
        }
        return $list;
    }
    public function redFunction(){//获取方法名
        $class=input('post.class');
        $class="app\admin\controller\\".$class;
        $newclass=new $class(Request::instance(),APP::instance());
        $own=get_class_methods($newclass);
        if($parent=get_parent_class($newclass)){
            $p_function=get_class_methods($parent);
            $newarr=array_diff($own,$p_function);
        }else{
            $newarr=$own;
        }
        echo json_encode($newarr);
    }
    public function ajax(){
        $role_id=input('get.role_id');
        $list=RolePower::where('role_id',$role_id)->select()->toArray();
        $newarr=[];
        foreach($list as $k=>$v){
            $newarr[]=$v['power_id'];
        }
        $powerinfo=Powers::select()->toArray();
        $powerinfo=$this->tree($powerinfo);
        $this->assign('powerinfo',$powerinfo);
        $this->assign('check',$newarr);
        return view();
    }
}