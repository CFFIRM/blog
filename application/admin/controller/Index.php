<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\facade\Session;
use app\admin\model\User;
use app\admin\model\Article;
use app\admin\model\Comment;
use app\admin\model\Collect;
use app\admin\model\Like;
use app\admin\model\Dislike;
use app\admin\model\Attention;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Index extends Controller 
{
    public function index(){
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $articleinfo=Article::where('a_private',0)->where('a_status',1)->join('user u','user.id=Article.a_user_id')->order('a_updatetime', 'desc')->paginate(10);
        $tuijian=Article::where('a_private',0)->order('a_like','desc')->limit(8)->select();
        $click=Article::where('a_private',0)->order('a_clickcount','desc')->limit(8)->select();
        $page = $articleinfo->render();
        // 模板变量赋值
        $this->assign('articleinfo',$articleinfo);
        $this->assign('page', $page);
        $this->assign('tuijian',$tuijian);
        $this->assign('click',$click);
        $this->assign('bg_userinfo',$bg_userinfo);//赋值给前台当前用户信息
        return view('showindex');
    }
    public function login(){
        if(request()->ispost()){
            $user_name=input('post.user_name');
            $user_pwd=input('post.user_pwd');
            $res=User::where('user_name', $user_name)->find();
            if($res['user_pwd']==md5($user_pwd)){
                if($res['user_status']==0){
                    Session::set('bg_userinfo',json_encode($res));
                    $res->last_logintime=time();
                    $res->login_count=$res['login_count']+1;
                    $res=$res->save();
                    if($res){
                        $this->success('登录成功','index');
                    }else{
                        $this->error('登录失败');
                    }
                }else if($res['user_status']==1){
                    if(time()>$res['user_disabletime']){
                        $res->user_status=0;
                    }
                    Session::set('bg_userinfo',json_encode($res));
                    $res->last_logintime=time();
                    $res->login_count=$res['login_count']+1;
                    $res=$res->save();
                    if($res){
                        $this->success('登录成功','own');
                    }else{
                        $this->error('登录失败');
                    }
                }else if($res['user_status']==2){
                    $this->error("不好意思,该账号已被封停,如要使用,请联系管理员");
                }
                
            }else{
                 $this->error('账号或密码错误');
            }
        }else{
            return view('index');
        }
    }
    public function sendemail($addAddress="",$connect="",$title=""){
        $mail=new PHPMailer;
        $mail->isSMTP();
        $mail->CharSet='utf-8';
        $mail->FromName="博文园";
        $mail->addReplyTo("cffirm0918@163.com");//设置回复的来源
        $mail->From = "cffirm0918@163.com";//设置发送的来源
        $mail->addAddress($addAddress);//设置发送给谁
        $mail->Subject = $title;//标题
        $mail->Body = $connect;//内容
        $mail->isHTML(true);//支持html
        //发送
        if($mail->send()){
            return 1;//成功
        }else{
            return 0;//失败
        }
    }
    public function findpwd(){
        if(request()->ispost()){
            $data=input("post.");
            $title="您正在找回密码";
            $connect="http://www.blog.com/admin/index/findnow?user_email={$data['user_email']}";
            $this->sendemail($data['user_email'],$connect);
        }else{
            return view();
        }
    }
    public function findnow(){
        if(request()->ispost()){
            $id=input('post.id');
            $data['user_pwd']=input('post.user_pwd');
            $data['again_pwd']=input('post.again_pwd');
            if($data['user_pwd']!=$data['again_pwd']){
                $this->error('两次密码输入不一致');
            }else{
                $user = new User;
                // save方法第二个参数为更新条件
                $res=$user->save($data,['id' => $id]);
                if($res){
                    $this->success("修改成功，正在转到登录界面",'login');
                }else{
                    $this->error("修改失败",'login');
                }
                
            }
        }else{
            $user_email=input('get.user_email');
            $res=User::where('user_email', $user_email)->field('id')->find();
            $this->assign('id',$res);
            return view();    
        }
        
    }
    public function register(){
        if(request()->ispost()){
            $data=input('post.');
            if($data['user_pwd']!=$data['again_pwd']){
                $this->error('两次密码输入不一致');
            }
            unset($data['again_pwd']);
            $data['user_pwd']=md5($data['user_pwd']);
            $data['user_addtime']=time();
            $user = new User;
            $res=$user->save($data);
            $id=$user->id;
            if($res){
                $title="注册成功,请激活";
                $connect="http://www.blog.com/admin/index/activate?id=".$id;
                $result=$this->sendemail($data['user_email'],$connect,$title);
                if($result){
                    $this->success("邮箱已成功发送，请前往邮箱激活，即将返回上一页面"); 
                }else{
                    $this->error("邮箱发送失败，即将返回上一页面");
                }
            }else{
                $this->error("添加失败");
            }
        }else{
            return view();
        }
        
    }
    public function activate(){
        $id=input('get.id');
        $user =User::get($id);
        $user->register_status=1;
        $res=$user->save();
        if($res){
            $this->success('激活成功','login');
        }else{
            $this->error('激活失败');
        }
    }
    public function layout(){
        Session::set('bg_userinfo',"");
        $this->success("退出成功",'index');
    }
    public function showonce(){
        $this->index();
        $a_id=input('get.a_id');
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $info=Article::where('a_id',$a_id)->find();
        $userinfo=User::where('id',$info['a_user_id'])->field('user_nickname')->find();
        $result=Like::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $result1=Dislike::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $collect=Collect::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $this->assign('like',$result);
        $this->assign('dislike',$result1);
        $this->assign('info',$info);
        $this->assign('shou',$collect);
        $this->assign('userinfo',$userinfo);
        return view();
    }
    /*public function changelike(){
        $a_id=input('post.a_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            $result=Like::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
            if($result){
               $art = Article::get($a_id);
               $art->a_like=$art['a_like']-1;
               $res=$art->save();
               if($res){
                Like::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->delete();
                $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                echo json_encode(['errcode'=>2,'msg'=>"取消赞成功",'data'=>$art]);
               }else{
                $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                echo json_encode(['errcode'=>3,'msg'=>"取消赞失败",'data'=>$art]);
               }
            }else{
                $art = Article::get($a_id);
                $art->a_like=$art['a_like']+1;
                $res=$art->save();
                if($res){
                    $like = new Like;
                    $like->save([
                        'article_id'  =>  $a_id,
                        'user_id' =>  $bg_userinfo['id']
                    ]);
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>4,'msg'=>"赞成功",'data'=>$art]);
                }else{
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>5,'msg'=>"赞失败",'data'=>$art]);
                }
            }
        }//点赞和取消点赞
    }
    public function changedislike(){
        $a_id=input('post.a_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            $result=Dislike::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
            if($result){
               $art = Article::get($a_id);
               $art->a_dislike=$art['a_dislike']-1;
               $res=$art->save();
               if($res){
                Dislike::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->delete();
                $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                echo json_encode(['errcode'=>2,'msg'=>"取消踩成功",'data'=>$art]);
               }else{
                $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                echo json_encode(['errcode'=>3,'msg'=>"取消踩失败",'data'=>$art]);
               }
            }else{
                $art = Article::get($a_id);
                $art->a_dislike=$art['a_dislike']+1;
                $res=$art->save();
                if($res){
                    $like = new Dislike;
                    $like->save([
                        'article_id'  =>  $a_id,
                        'user_id' =>  $bg_userinfo['id']
                    ]);
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>4,'msg'=>"踩成功",'data'=>$art]);
                }else{
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>5,'msg'=>"踩失败",'data'=>$art]);
                }
            }
        }//点踩和取消点踩
    }*/
    public function changelike(){
        $a_id=input('post.a_id');
        $user_id=input('post.user_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            if($user_id == $bg_userinfo['id']){
                echo json_encode(['errcode'=>1,'msg'=>"您自己不能赞自己"]);  
            }else{
                $result=Like::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
                $art = Article::get($a_id);
                $art->a_like=$art['a_like']+1;
                $res=$art->save();
                if($res){
                    $like = new Like;
                    $like->save([
                        'article_id'  =>  $a_id,
                        'user_id' =>  $bg_userinfo['id']
                    ]);
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>4,'msg'=>"赞成功",'data'=>$art]);
                }else{
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>5,'msg'=>"赞失败",'data'=>$art]);
                }
          }
        }
    }
    public function changedislike(){
        $a_id=input('post.a_id');
        $user_id=input('post.user_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            if($user_id == $bg_userinfo['id']){
                echo json_encode(['errcode'=>1,'msg'=>"您自己不能踩自己"]);  
            }else{
                $result=Dislike::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
                $art = Article::get($a_id);
                $art->a_dislike=$art['a_dislike']+1;
                $res=$art->save();
                if($res){
                    $like = new Dislike;
                    $like->save([
                        'article_id'  =>  $a_id,
                        'user_id' =>  $bg_userinfo['id']
                    ]);
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>4,'msg'=>"踩成功",'data'=>$art]);
                }else{
                    $art = Article::field('a_like,a_dislike')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>5,'msg'=>"踩失败",'data'=>$art]);
                }
            }
        }
    }
    public function changecollect(){
        $a_id=input('post.a_id');
        $user_id=input('post.user_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            if($user_id == $bg_userinfo['id']){
                echo json_encode(['errcode'=>1,'msg'=>"您自己不能收藏自己的文章"]);  
            }else{
                $result=Collect::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
                $art = Article::get($a_id);
                $art->a_collect=$art['a_collect']+1;
                $res=$art->save();
                if($res){
                    $like = new Collect;
                    $like->save([
                        'article_id'  =>  $a_id,
                        'user_id' =>  $bg_userinfo['id'],
                        'addtime'=>time()
                    ]);
                    $art = Article::field('a_like,a_dislike,a_collect')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>4,'msg'=>"收藏成功",'data'=>$art]);
                }else{
                    $art = Article::field('a_like,a_dislike,a_collect')->where('a_id',$a_id)->find();
                    echo json_encode(['errcode'=>5,'msg'=>"收藏失败",'data'=>$art]);
                }
            }
        }
    }
    public function addattention(){
        $user_id=input('post.user_id');
        if(empty(Session::get('bg_userinfo'))){
            echo json_encode(['errcode'=>0,'msg'=>"您还未登录"]);
        }else{
            $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
            if($user_id == $bg_userinfo['id']){
                echo json_encode(['errcode'=>1,'msg'=>"您自己不能关注自己"]);  
            }else{
                $result=Attention::where('attention_user_id',$user_id)->where('user_id',$bg_userinfo['id'])->find();
                if(!$result){
                    $attention = new Attention;
                    $attention->save([
                        'attention_user_id'  =>  $user_id,
                        'user_id' =>  $bg_userinfo['id'],
                        'addtime'=>time()
                    ]);
                    echo json_encode(['errcode'=>4,'msg'=>"关注成功"]);
                }else{
                    echo json_encode(['errcode'=>5,'msg'=>"您已关注过此用户"]);
                }
            }
        }
    }
    public function loadcomment(){
        $a_id=input('post.a_id');
        $load=input('post.load');
        $comment=Comment::where('article_id',$a_id)->where('comment_status',1)->order('comment_addtime','desc')->limit($load,3)->join('user c','Comment.user_id = c.id')->field('user_nickname,id,comment_text,comment_addtime')->select();
        foreach ($comment as $key => $value) {
            $value['comment_addtime']=date("Y-m-d H:i",$value['comment_addtime']); 
        }
        if(!isset($comment[0])){
           echo json_encode(['errcode'=>0,'msg'=>'暂无评论']);
        }else{
            $load=$load+3;
           echo json_encode(['data'=>$comment,'load'=>$load]);   
        }
         
    }
}
