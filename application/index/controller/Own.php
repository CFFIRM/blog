<?php
namespace app\index\controller;

use think\Controller;
use app\index\controller\Index;
use think\facade\Session;
use app\index\model\Article;
use app\index\model\Like;
use app\index\model\Collect;
use app\index\model\Comment;
use app\index\model\Word;
use app\index\model\User;
use app\index\model\Attention;
use app\index\model\Message;
use think\facade\Cookie;


class Own extends Controller
{
    public function showinfo(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $like=Like::where('user_id',$bg_userinfo['id'])->count();
        $userimg=User::where('id',$bg_userinfo['id'])->field('user_img')->find();
        $article=Article::where('a_user_id',$bg_userinfo['id'])->count();
        $collect=Collect::where('user_id',$bg_userinfo['id'])->count();
        $comment=Comment::where('user_id',$bg_userinfo['id'])->count();
        $attention=Attention::where('user_id',$bg_userinfo['id'])->count();
        $this->assign('a_count',$article);
        $this->assign('user_img',$userimg);
        $this->assign('like_count',$like);
        $this->assign('collect_count',$collect);
        $this->assign('comment_count',$comment);
        $this->assign('attention_count',$attention);
        return view("Own/showinfo");
    }
    public function mycollect(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Collect::where('user_id',$bg_userinfo['id'])->join('article a','collect.article_id=a.a_id')->paginate(10)->each(function($item,$key){
                $item->a_content=preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0|\&#39\;)/", " ", strip_tags($item->a_content));
            });
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/mycollect");
    }
    public function mycomment(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Comment::where('user_id',$bg_userinfo['id'])->join('article a','comment.article_id=a.a_id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/mycomment");
    }
    public function likeme(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Attention::where('attention_user_id',$bg_userinfo['id'])->join('user u','attention.user_id=u.id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/likeme");
    }
    public function like(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Like::where('user_id',$bg_userinfo['id'])
                    ->join('article a','like.article_id=a.a_id')
                    ->join('user u','a.a_user_id=u.id')
                    ->field('a_id,a_title,a_content,a_updatetime,a_like,a_dislike,a_collect,a_clickcount,id,user_nickname')
                    ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/like");
    }
    public function set(){
        if(request()->ispost()){
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            $data=input('post.');
            $user=new User;
            $res=$user->save($data,['id'=>$bg_userinfo['id']]);
            if($res){
                $result=User::where('id',$bg_userinfo['id'])->field('user_nickname,user_tel,user_email,user_img')->find();
                echo json_encode(['data'=>$result]);
            }else{
                echo json_encode(['errcode'=>0]);//更改失败
            }
        }else{
            $meun=new Index();$meun->index();
            return view("Own/set");    
        }
    }
    public function changeuser(){
        $data=input('post.');
        $arr=array_keys($data);
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $user = User::get($bg_userinfo['id']);
        switch ($arr[0]) {
            case 'user_name':
                $user->user_name=$data['user_name'];
                break;
            case 'user_tel':
                $user->user_tel=$data['user_tel'];
                break;
            case 'user_email':
                $user->user_email=$data['user_email'];
                break;
            case 'old_pwd':
                $user->user_pwd=$data['user_pwd'];
                break;
            case 'user_nickname':
                $user->user_nickname=$data['user_nickname'];
                break;
        }
        $res=$user->save();
        $result=User::where('id',$bg_userinfo['id'])->find();
        if($res){
            Session::set('bg_userinfo',json_encode($result));
            $this->success('更改成功');
        }else{
            $this->error('更改失败');
        }
    }
    public function fillerword($string){
        $stringAfter=$string;
        $wordarr=Word::field('word_name')->select()->toArray();
        $newstring="";    
        foreach ($wordarr as $key => $value) {
            $newstring.=$value['word_name'].',';
        }
        $asd=substr($newstring,0,-1);
        $newwordarr=explode(',',$asd);
        $pattern = "/".implode("|",$newwordarr)."/i";
        if(preg_match_all($pattern,$string, $matches)){
            $patternList = $matches[0];
            $sensitiveWord = implode(',', $patternList); //敏感词数组转字符串
            $replaceArray = array_combine($patternList,array_fill(0,count($patternList),' (*)')); //把匹配到的数组进行合并，替换使用
            $stringAfter = strtr($string, $replaceArray); //结果替换
        }
        return $stringAfter;
    }
    public function write(){
        if(request()->ispost()){
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            if($bg_userinfo['user_status']==1){
                $this->erro("您的账号处于禁用状态，目前不能发表文章");
            }else{
                $data=input('post.');
                $data['a_user_id']=$bg_userinfo['id'];
                $data['a_updatetime']=time();
                $data['a_content']=$this->fillerword($data['a_content']);
                $article = new Article;
                $res=$article->save($data);
                if($res){
                    $this->success('发表成功，待管理员审核','Own/mybolg');
                }else{
                    $this->error('发表失败');
                }
            }
        }else{
            $meun=new Index();$meun->index();
            return view("Own/write");
        }
    }
    public function mybolg(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $id=$bg_userinfo['id'];
        // 查询状态为1的用户数据 并且每页显示10条数据
        $list = Article::where('a_user_id',$id)->paginate(10)->each(function($k,$v){
            $k->a_content=strip_tags($k->a_content);
        });
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        return view("Own/mybolg");
    }
    public function anyone(){
        $meun=new Index();$meun->index();
        $id=input('get.id');
        $page=input('get.page');
        if(empty($page)){
            Cookie::set('user_id',$id);
            $userinfo=Article::where('a_user_id',$id)->join('user u','article.a_user_id=u.id')->field('id,user_nickname,a_id,a_title,a_content,a_updatetime,a_like,a_dislike,a_collect')->paginate(10);
            $userdeinfo=Article::where('a_user_id',$id)->join('user u','article.a_user_id=u.id')->field('id,user_nickname')->find();
            $page = $userinfo->render();
            // 模板变量赋值
            $this->assign('list', $userinfo);
            $this->assign('info', $userdeinfo);
            $this->assign('page', $page);
            return view("Own/anyone");
        }else{
            $user_id=Cookie::get('user_id');
            $userinfo=Article::where('a_user_id',$user_id)->join('user u','article.a_user_id=u.id')->field('id,user_nickname,a_id,a_title,a_content,a_updatetime,a_like,a_dislike,a_collect')->paginate(10);
            $userdeinfo=Article::where('a_user_id',$user_id)->join('user u','article.a_user_id=u.id')->field('id,user_nickname')->find();
            $page = $userinfo->render();
            // 模板变量赋值
            $this->assign('list', $userinfo);
            $this->assign('info', $userdeinfo);
            $this->assign('page', $page);
            return view("Own/anyone");
        }
        
    }
    public function myattention(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Attention::where('user_id',$bg_userinfo['id'])->join('user u','attention.attention_user_id=u.id')->field('u.id,attention.addtime,user.user_nickname,user.user_addtime')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/myattention");   
    }
    public function message(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $res=Message::where('user_id',$bg_userinfo['id'])->paginate(5);
        $page = $res->render();
        // 模板变量赋值
        $this->assign('list', $res);
        $this->assign('page', $page);
        // 渲染模板输出
        return view("Own/message");   
    }
    public function showmessage(){
        $meun=new Index();$meun->index();
        $id=input('get.id');
        $res=Message::where('id',$id)->join('admin a','message.admin_id=a.admin_id')->field('content,addtime,admin_login_name')->find();
        $res->status=1;
        $res->save();
        $this->assign('info',$res);
        return view("Own/showmessage");
    }
    public function deleteblog(){
        $a_id=input('post.a_id');
        $res=Article::where('a_id',$a_id)->delete();
        if($res){
            echo json_encode(['errcode'=>1,'msg'=>"删除成功"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"删除失败"]);
        }
    }
    public function deleteattention(){
        $id=input('post.id');
        $res=Attention::where('id',$id)->delete();
        if($res){
            echo json_encode(['errcode'=>1,'msg'=>"删除成功"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"删除失败"]);
        }
    }
    public function deletecomment(){
        $id=input('post.id');
        $res=Comment::where('comment_id',$id)->delete();
        if($res){
            echo json_encode(['errcode'=>1,'msg'=>"删除成功"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"删除失败"]);
        }
    }
    public function deletemessage(){
        $id=input('post.id');
        $res=Message::where('id',$id)->delete();
        if($res){
            echo json_encode(['errcode'=>1,'msg'=>"删除成功"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"删除失败"]);
        }
    }
    public function deletecollect(){
        $id=input('post.id');
        $asd=Message::where('id',$id)->field('article_id')->find();
        $asd->a_collect=$asd->a_collect-1;
        $asd->save();
        $res=Collect::where('id',$id)->delete();
        if($res){
            echo json_encode(['errcode'=>1,'msg'=>"删除成功"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"删除失败"]);
        }
    }
    public function upload(){
        
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->move( 'static/userimg/');
        if($info){
            // 成功上传后 获取上传信息
            $data=$info->getSaveName();
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
        if($data){
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            $user=User::where('id',$bg_userinfo['id'])->find();
            $user->user_img=$data;
            $user->save();
            return $data;
        }
    }
}