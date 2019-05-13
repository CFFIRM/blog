<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\Article as Wen;
use app\index\model\Cat;
use app\index\model\User;
use app\index\controller\Index;
use app\index\controller\Common;
use think\facade\Request;
class Article extends Common
{
	public function article_list(){
        $meun=new Index();$meun->after();
        $res=Wen::paginate(5);
        return view('article_list',['page'=>$res]);    
	}
	public function addarticle(){
        if(request()->ispost()){
        	$data=input('post.');
            $data['a_updatetime']=time();
            $wen=new Wen();
            $res=$wen->save($data);
            if($res){
                $this->redirect('Article/article_list?a_fid='.$data['a_fid']);
            }else{
                $this->error('发生了未知的错误');
            }    
        }else{
            $a_fid=$this->request->param('a_fid');
            $meun=new Index();$meun->after();
            $this->assign('a_fid',$a_fid);
            return view();    
        }
        
	}
	public function deletearticle(){
		$a_id=input('get.id');
    	$res=Wen::where('a_id',$a_id)->delete();
    	if($res){
    		$this->success('删除成功');
    	}else{
    		$this->error('f_list');
    	}
	}
	public function compilearticle(){
        if(request()->ispost()){
        	$a_id=input('post.a_id');
            $a_fid=input('post.a_fid');
        	$data['a_title']=input('post.a_title');
        	$data['a_content']=input('post.a_content');
        	$data['a_status']=input('post.a_status');
            $data['a_updatetime']=time();
            /*$wen=new Wen();
            $res=$wen->save($data,['a_id'=>$a_id]);*/
            $res=Wen::where('a_id',$a_id)->update($data);
            if($res){
                $this->redirect('Article/article_list?a_fid='.$a_fid    );
            }else{
                $this->error('发生了未知的错误');
            }
        }else{
            $a_id=input('get.id');
            $meun=new Index();$meun->after();
            $res=Wen::where('a_id',$a_id)->find()->toArray();
            $this->assign('info',$res);
            return view();
        }
    }
    public function ajax(){
        $a_fid=$this->request->param('a_fid');
            $res=Wen::where('a_fid',$a_fid)->select()->toArray();
            $newarr=[];
            foreach($res as $k=>$v){
                if($v['a_content']==""){
                    $newarr[]=$v['a_title'];
                }
            }
            echo json_encode($newarr);
    }
    public function change(){
        $a_id=input('post.a_id');
        $user = Wen::where('a_id',$a_id)->find();
        if($user['a_status']==0){
            $user->a_status     = 1;    
        }else{
            $user->a_status     = 0;    
        }
        $user->save();
        $res = Wen::where('a_id',$a_id)->field('a_status')->find();
        echo json_encode($res);
    }
    public function showinfo(){
        $meun=new Index();$meun->after();
        $a_id=input('get.a_id');
        $user = Wen::where('a_id',$a_id)->find()->toArray();
        $userinfo=User::where('id',$user['a_user_id'])->field('user_nickname')->find()->toArray();
        return view('showinfo',['info'=>$user,'user_name'=>$userinfo]);
    }
}