<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\Admin;
use app\index\model\Powers;
use think\facade\Session;
class Index extends Controller 
{
    public function after()
    {
        $userinfo=Session::get('userinfo');
        if(!empty($userinfo)){
            $userinfo=json_decode($userinfo,true);
            $power=new Powers();
            $res=$power->Getall();
            $last=$this->tree($res);
            $this->assign('listinfo',$last);
            $this->assign('userinfo',$userinfo);
            return view('index');    
        }else{
            return $this->redirect('index/index');
        }
        
    }

    public function index(){
        return view('login');
    } 
    public function dologin(){
        $user_name=input('post.admin_login_name');
        $user_pwd=input('post.admin_login_pwd');
        $res=Admin::where('admin_login_name', $user_name)->find();
        if($res['admin_updatetime']+(30*86400)<time()){
            echo json_encode(['code'=>0,'msg'=>"请重新更换密码登录"]);
        }else{
            if($res['admin_login_pwd']==md5($user_pwd)){
                Session::set('userinfo',json_encode($res));
                $res->admin_last_logintime=time();
                $res->admin_login_count=$res['admin_login_count']+1;
                $res->save();
                echo json_encode(['code'=>1,'msg'=>"登录成功"]);    
            }else{
                echo json_encode(['code'=>12121,'msg'=>"登录失败"]);    
            }    
        }
    }
    public function tree($power,$level=1,$f_id=0){
        static $arr=[];
        foreach($power as $k=>$v){
            if($v['f_id']==$f_id && $v['is_show']==0){
                $v['level']=$level;
                $v['url']=$v['controller']."/".$v['function'];
                $arr[]=$v;
                $this->tree($power,$level+1,$v['power_id']);
            }
        }
        return $arr;
    }
    public function logout(){
        Session::set('userinfo','');
        $this->redirect('index/index');
    }
    public function forgot(){
        if(request()->ispost()){
            $admin_id=input('post.admin_id');
            $admin_login_pwd=input('post.admin_login_pwd');
            $again_pwd=input('post.again_pwd');
            if($admin_login_pwd!=$again_pwd){
                return $this->error("两次输入密码不一致");
            }else{
                $admin = Admin::where('admin_id',$admin_id)->field('admin_login_pwd,admin_frist_pwd,admin_second_pwd,admin_thrid_pwd')->find();
                if(empty($admin['admin_frist_pwd'])){
                    $admin->admin_frist_pwd=$admin['admin_login_pwd'];
                }else if(empty($admin['admin_second_pwd'])){
                    $admin->admin_second_pwd=$admin['admin_login_pwd'];
                }else if(empty($admin['admin_thrid_pwd'])){
                    $admin->admin_thrid_pwd=$admin['admin_login_pwd'];
                }else{
                    $admin->admin_frist_pwd=$admin['admin_second_pwd'];
                    $admin->admin_second_pwd=$admin['admin_thrid_pwd'];
                    $admin->admin_thrid_pwd=$admin['admin_login_pwd'];
                }
                $admin->admin_login_pwd=md5($admin_login_pwd);
                $admin->admin_updatetime    = time();
                $res=$admin->save();
                if($res){
                    return $this->success("修改成功",'index');
                }else{
                    return $this->error("修改失败");
                }    
            }
        }else{
            return view();    
        }
    }
    public function check(){
        $name=input('post.name');
        $email=input('post.email');
        $res=Admin::where('admin_login_name',$name)->where('admin_email',$email)->field('admin_id')->find();
        if(!isset($res['admin_id'])){
            echo json_encode(['errcode'=>0,'msg'=>"未找到数据"]);
        }else{
            echo json_encode(['errcode'=>1,'data'=>$res]);
        }
    }
    public function checkpwd(){
        $pwd=input('post.pwd');
        $id=input('post.id');
        $res=Admin::where('admin_id',$id)->field('admin_frist_pwd,admin_second_pwd,admin_thrid_pwd')->find();
        if(md5($pwd)==$res['admin_frist_pwd']||md5($pwd)==$res['admin_second_pwd']||md5($pwd)==$res['admin_thrid_pwd']){
            echo json_encode(['error'=>1]);//代表与前三次有重复
        }else{
            echo json_encode(['error'=>2]);//代表没有重复
        }
    }
}
