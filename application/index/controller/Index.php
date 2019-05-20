<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Cache;
use app\index\model\User;
use app\index\model\Article;
use app\index\model\Comment;
use app\index\model\Collect;
use app\index\model\Like;
use app\index\model\Dislike;
use app\index\model\Attention;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use app\index\model\Link;
use app\index\model\Advertising;
class Index extends Controller 
{
    public function index(){
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $articleinfo=Article::where('a_private',0)->where('a_status',1)->join('user u','user.id=Article.a_user_id')->order('a_updatetime', 'desc')->paginate(10)->each(function($item,$key){
            $item->a_content=strip_tags($item->a_content);
        });
        $tuijian=Article::where('a_private',0)->order('a_like','desc')->limit(8)->select();
        $click=Article::where('a_private',0)->order('a_clickcount','desc')->limit(8)->select();
        $Link=Link::order('l_addtime','desc')->limit(8)->select();
        $page = $articleinfo->render();
        // 模板变量赋值
        $this->assign('articleinfo',$articleinfo);
        $this->assign('page', $page);
        $this->assign('tuijian',$tuijian);
        $this->assign('click',$click);
        $this->assign('bg_userinfo',$bg_userinfo);//赋值给前台当前用户信息
        $this->assign('link_info',$Link);//赋值给前台当前用户信息
        return view('Index/showindex');
    }
    public function login(){
        if(request()->ispost()){
            $user_name=input('post.user_name');
            $user_pwd=input('post.user_pwd');
            $res=User::where('user_name', $user_name)->find();
            if($res['user_pwd']==md5($user_pwd)){
                if($res['register_status']==1){
                    if($res['user_status']==0){
                        Session::set('bg_userinfo',json_encode($res));
                        $res->last_logintime=time();
                        $res->login_count=$res['login_count']+1;
                        $res=$res->save();
                        if($res){
                            $this->success('登录成功','Own/showinfo');
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
                            $this->success('登录成功','Own/showinfo');
                        }else{
                            $this->error('登录失败');
                        }
                    }else if($res['user_status']==2){
                        $this->error("不好意思,该账号已被封停,如要使用,请联系管理员");
                    }
                }else{
                    $this->error("您的账号尚未激活");
                }
            }else{
                 $this->error('账号或密码错误');
            }
        }else{
            return view('Index/index');
        }
    }
    public function sendemail($addAddress="",$connect="",$title=""){
        $mail=new PHPMailer;
        $mail->isSMTP();
        $mail->CharSet='utf-8';
        $mail->FromName="乐享博客";
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
            $user=User::where('user_email',$data['user_email'])->field('id')->find();
            $title="您正在找回密码";
            $arr=[
                'user_email'=>$data['user_email'],
                'default'=>'cffirm',
                'id'=>$user['id'],
                'time'=>time()
            ];
            $token=base64_encode(http_build_query($arr));
            $connect="请将此链接复制到浏览器内http://47.101.44.77:9918/admin/index/findnow?token=".$token;
            $res=$this->sendemail($data['user_email'],$connect,$title);
            if($res){
                Cookie::set('token',$token,1800);
                $this->success('发送成功，请去邮箱查看，邮件有效期为30分钟，即将返回上一页面');
            }else{
                $this->error('发送失败');
            }
        }else{
            return view();
        }
    }
    public function findnow(){
        if(request()->ispost()){
            $id=input('post.id');
            $data['user_pwd']=input('post.user_pwd');
            $again_pwd=input('post.again_pwd');
            if($data['user_pwd']!=$again_pwd){
                $this->error('两次密码输入不一致');
            }else{
                $data['user_pwd']=md5($data['user_pwd']);
                $user = new User;
                // save方法第二个参数为更新条件
                $res=$user->save($data,['id' => $id]);
                if($res){
                    $this->success("修改成功，正在转到登录界面",'Index/login');
                }else{
                    $this->error("修改失败",'Index/login');
                }
                
            }
        }else{
            $user_token=input('get.token');
            $cok_token=Cookie::get('token');
            if(Cookie::has('token')){
                if($user_token==$cok_token){
                    parse_str(base64_decode($user_token),$data);
                    $this->assign('info',$data);
                    return view();            
                }else{
                    $this->error("请检查链接是否完整");
                }
                
            }else{
                $this->error('邮件已失效，请重新申请',"Index/login");
            }
        }
        
    }
    public function register(){
        if(request()->ispost()){
            $data=input('post.');
            if($data['user_pwd']!=$data['again_pwd']){
                $this->error('两次密码输入不一致');
            }
            unset($data['again_pwd']);
            Cache::set('user_register_info',$data,3600);
            $title="注册成功,请激活";
            $connect="请将此链接复制到浏览器内http://47.101.44.77:9918/admin/index/activate,链接将在30分钟后过期，请尽快激活";
            $result=$this->sendemail($data['user_email'],$connect,$title);
            if($result){
                $this->success("邮箱已成功发送，请前往邮箱激活，邮件有效期为30分钟，即将返回上一页面"); 
            }else{
                $this->error("邮箱发送失败，即将返回上一页面");
            }
        }else{
            return view('Index/register');
        }
    }
    public function checkregister(){
        $data=input('post.');
        $arr=array_keys($data);
        switch ($arr[0]) {
            case 'user_name':
                $res=User::where('user_name',$data['user_name'])->find();
                break;
            case 'user_nickname':
                $res=User::where('user_nickname',$data['user_nickname'])->find();
                break;
            case 'user_email':
                $res=User::where('user_email',$data['user_email'])->find();
                break;
            case 'user_tel':
                $res=User::where('user_tel',$data['user_tel'])->find();
                break;
        }
        if(empty($res)){
            echo json_encode(['errcode'=>1,'msg'=>"数据为空"]);
        }else{
            echo json_encode(['errcode'=>2,'msg'=>"有数据"]);
        }
    }
    public function sendphonemsg($phone="",$content=""){
        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
            );
            $smsapi = "http://api.smsbao.com/";
            $user = "li932959092"; //短信平台帐号
            $pass = md5("li932959092"); //短信平台密码
            $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode("【乐享博客】".$content);
            $result =file_get_contents($sendurl);
            return $statusStr[$result];
    }
    public function activate(){
        $data=Cache::get('user_register_info');
        $data['user_pwd']=md5($data['user_pwd']);
        $data['user_addtime']=time();
        $data['register_status']=1;
        $user = new User;
        $res=$user->save($data);
        if($res){
            $this->success('激活成功','Index/login');
        }else{
            $this->error('激活失败');
        }
    }
    public function layout(){
        Session::set('bg_userinfo',"");
        $this->success("退出成功",'Index/index');
    }
    public function showonce(){
        $this->index();
        $a_id=input('get.a_id');
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
        $info=Article::where('a_id',$a_id)->find();
        $info->a_clickcount=$info->a_clickcount+1;
        $info->save();
        $userinfo=User::where('id',$info['a_user_id'])->field('user_nickname')->find();
        $result=Like::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $result1=Dislike::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $collect=Collect::where('article_id',$a_id)->where('user_id',$bg_userinfo['id'])->find();
        $attention=Attention::where('user_id',$bg_userinfo['id'])->find();
        $advertising=Advertising::select();
        $this->assign('like',$result);
        $this->assign('dislike',$result1);
        $this->assign('info',$info);
        $this->assign('shou',$collect);
        $this->assign('userinfo',$userinfo);
        $this->assign('attention',$attention);
        $this->assign('advertising',$advertising);
        return view("Index/showonce");
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
