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
            $data=input('post.');
            if($data['admin_login_pwd']!=$data['again_pwd']){
                return $this->error("两次输入密码不一致");
            }else{
                $user = new Admin;
                //save方法第二个参数为更新条件
                $res=$user->save([
                    'admin_login_pwd'  => md5($data['admin_login_pwd']),
                ],['admin_login_name' => $data['admin_login_name']]);
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
}
