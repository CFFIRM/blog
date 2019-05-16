<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Index;
use think\facade\Session;
use app\admin\model\Article;
use app\admin\model\Like;
use app\admin\model\Collect;
use app\admin\model\Comment;
use app\admin\model\Word;
use app\admin\model\User;
use app\admin\model\Attention;
use think\facade\Cookie;


class Own extends Controller
{
    public function showinfo(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $like=Like::where('user_id',$bg_userinfo['id'])->count();
        $article=Article::where('a_user_id',$bg_userinfo['id'])->count();
        $collect=Collect::where('user_id',$bg_userinfo['id'])->count();
        $comment=Comment::where('user_id',$bg_userinfo['id'])->count();
        $attention=Attention::where('user_id',$bg_userinfo['id'])->count();
        $this->assign('a_count',$article);
        $this->assign('like_count',$like);
        $this->assign('collect_count',$collect);
        $this->assign('comment_count',$comment);
        $this->assign('attention_count',$attention);
        return view();
    }
    public function mycollect(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Collect::where('user_id',$bg_userinfo['id'])->join('article a','Collect.article_id=a.a_id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view();
    }
    public function mycomment(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Comment::where('user_id',$bg_userinfo['id'])->join('article a','Comment.article_id=a.a_id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view();
    }
    public function likeme(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Attention::where('attention_user_id',$bg_userinfo['id'])->join('user u','Attention.user_id=u.id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view();
    }
    public function like(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $list = Like::where('user_id',$bg_userinfo['id'])->join('article a','like.article_id=a.a_id')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return view();
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
            return view();    
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
            $data=input('post.');
            $data['a_user_id']=$bg_userinfo['id'];
            $data['a_updatetime']=time();
            $data['a_content']=$this->fillerword($data['a_content']);
            $article = new Article;
            $res=$article->save($data);
            if($res){
                $this->success('发表成功，待管理员审核','mybolg');
            }else{
                $this->error('发表失败');
            }
        }else{
            $meun=new Index();$meun->index();
            return view();
        }
    }
    public function mybolg(){
        $meun=new Index();$meun->index();
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $id=$bg_userinfo['id'];
        // 查询状态为1的用户数据 并且每页显示10条数据
        $list = Article::where('a_user_id',$id)->paginate(10);
        // 获取分页显示
        $page = $list->render();
        // 模板变量赋值
        $this->assign('list', $list);
        $this->assign('page', $page);
        return view();
    }
    public function anyone(){
        $meun=new Index();$meun->index();
        $id=input('get.id');
        $userinfo=Article::where('a_user_id',$id)->join('user u','Article.a_user_id=u.id')->field('id,user_nickname,a_id,a_title,a_content,a_updatetime,a_like,a_dislike,a_collect')->paginate(10);
        $page = $userinfo->render();
        // 模板变量赋值
        $this->assign('list', $userinfo);
        $this->assign('page', $page);
        return view();
    }
}