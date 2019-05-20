<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Article as Wen;
use app\admin\model\Cat;
use app\admin\model\User;
use think\facade\Session;
use app\admin\model\Message;
use app\admin\controller\Index;
use app\admin\controller\Common;
use think\facade\Request;
class Article extends Common
{
	public function article_list(){//通过
        $meun=new Index();$meun->after();
        $list=Wen::where('a_status',1)->paginate(10);
        $page = $list->render();
        // 模板变量赋值
        $this->assign('info', $list);
        $this->assign('page', $page);
        return view('article_list');    
	}
    public function article_list_shen(){//审核中
        $meun=new Index();$meun->after();
        $list=Wen::where('a_status',0)->paginate(10);
        $page = $list->render();
        // 模板变量赋值
        $this->assign('info', $list);
        $this->assign('page', $page);
        return view('article_list');    
    }
    public function article_list_pass(){//未通过
        $meun=new Index();$meun->after();
        $list=Wen::where('a_status',2)->paginate(10);
        $page = $list->render();
        // 模板变量赋值
        $this->assign('info', $list);
        $this->assign('page', $page);
        return view('article_list');    
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
		$a_id=input('get.a_id');
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
            $a_id=input('get.a_id');
            $meun=new Index();$meun->after();
            $res=Wen::where('a_id',$a_id)->find()->toArray();
            $this->assign('info',$res);
            return view();
        }
    }
    public function change(){
        $userinfo=json_decode(Session::get('userinfo'),true);
        $a_id=input('post.a_id');
        $text=input('post.message');
        $user_id=input('post.user_id');
        $a_status=input('post.a_status');
        $user = Wen::where('a_id',$a_id)->find();
        if($a_status==1){
            $user->a_status     = 1;
            $user->a_shentime   = time();
            $user->admin_id    =$userinfo['admin_id'];
        }else{
            $user->a_status     = 2;
            $user->a_shentime   =time();
            $user->admin_id    =$userinfo['admin_id'];
        }
        $result=$user->save();
        $msg=new Message;
        $msg->save([
            'content'=>$text,
            'user_id'=>$user_id,
            'admin_id'=>$userinfo['admin_id'],
            'addtime'=>time()
        ]);
        if($result){
            $res = Wen::where('a_id',$a_id)->field('a_status')->find();
            if(request()->ispost()){
                if($res){
                    $this->success("审核成功",'article_list');
                }else{
                    $this->error("审核失败");
                }
            }else{
                echo json_encode($res);
            }    
        }else{
            $this->error("更改状态失败");
        }
    }
    public function showinfo(){
        $meun=new Index();$meun->after();
        $a_id=input('get.a_id');
        $user = Wen::where('a_id',$a_id)->find()->toArray();
        $userinfo=User::where('id',$user['a_user_id'])->field('user_nickname')->find()->toArray();
        return view('showinfo',['info'=>$user,'user_name'=>$userinfo]);
    }
}