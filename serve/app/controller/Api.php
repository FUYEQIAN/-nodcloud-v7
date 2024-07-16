<?php
namespace app\controller;
use app\BaseController;
use app\model\User;
use think\facade\Request;
class Api extends BaseController{
    //登陆
    public function login(){
        $input=input('post.');
        if(existFull($input,['user','pwd','uuid'])){
            $sql=fastSql($input,[
                [['user'=>'user|tel'],'eq'],
                ['pwd','md5']
            ]);
            $user=User::where($sql)->field(['id','name','tel','frame','user','img'])->find();
            if(empty($user)){
                $result=['state'=>'error','info'=>'账号|手机号或密码不正确!'];
            }else{
                $token=token();
                cache($token,['user'=>$user['id'],'frame'=>[]]);
                cache($input['uuid'],null);
                pushLog('登录成功',$user['id']);
                $result=['state'=>'success','info'=>$user,'token'=>$token];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //获取验证
    public function captcha(){
        $uuid=uuid();
        $captcha=new \org\Captcha();
        $info=$captcha->entry();
        cache($uuid,$info['code']);
        return json(['state'=>'success','info'=>['uuid'=>$uuid,'data'=>$info['data']]]);
    }
    //运行数据
    public function runData(){
        return json([
            'state'=>'success',
            'info'=>[
                'login'=>checkLogin(),
                'sys'=>getSys(['name','company','icp','notice']),
                'ver'=>config('soft.version')
            ]
        ]);
    }
}