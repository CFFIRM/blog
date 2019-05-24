<?php
namespace app\index\controller;

use think\Controller;
use think\facade\Session;
use app\index\model\Comment as C;
use app\index\model\Word;

class Comment extends Controller
{
    public function addcomment(){
        $bg_userinfo=json_decode(Session::get('bg_userinfo'),true);
<<<<<<< HEAD
        if($bg_userinfo['user_status']==1){
            echo json_encode(['errcode'=>3,'msg'=>"您的账号处于禁用状态，目前不能发表评论"]);
        }else{
            $data=input('post.');
            $data['comment_addtime']=time();
            $data['comment_text']=$this->fillerword($data['comment_text']);
            $data['user_id']=$bg_userinfo['id'];
            $c=new C;
            $res=$c->save($data);
            if($res){
                echo json_encode(['errcode'=>1,'msg'=>"评论成功，待审核"]);
            }else{
                echo json_encode(['errcode'=>2,'msg'=>"评论失败"]);
            }
        }
    	
=======
    	$data=input('post.');
    	$data['comment_addtime']=time();
        $data['comment_text']=$this->fillerword($data['comment_text']);
        $data['user_id']=$bg_userinfo['id'];
    	$c=new C;
    	$res=$c->save($data);
    	if($res){
    		echo json_encode(['errcode'=>1,'msg'=>"评论成功，待审核"]);
    	}else{
    		echo json_encode(['errcode'=>2,'msg'=>"评论失败"]);
    	}
>>>>>>> 12ba1653232a4a26fbda14216b06747680cbb775
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
}