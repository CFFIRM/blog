<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\facade\Session;
use app\admin\model\Article;
use app\admin\model\Powers;
class Index extends Controller 
{
    public function index(){
        return view();
    }
    public function Aboutus(){
        $a_fid=Powers::where('power_name',"关于我们")->field('power_id')->find()->toArray();
        $res=Article::where('a_fid',$a_fid['power_id'])->find()->toArray();
        $this->assign('article',$res);
        return view();
    }
    public function sale_service(){
        return view();
    }
    public function product(){
        return view();
    }
    public function refernece(){
        return view();
    }
    public function contact_us(){
        return view();
    }
}
