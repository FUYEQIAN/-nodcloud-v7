<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Deploy;
use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
class Ali extends Acl {
    //阿里支付
    
    //当面付
    public function pay() {
        $input=input('post.');
        if(existFull($input,['number','money','code'])){
            //读取配置
            $deploy=getFrameDeploy();
            if(!empty($deploy)){
                //配置数据
                $config=new Config;
                $config->protocol = 'https';
                $config->gatewayHost = 'openapi.alipay.com';
                $config->appId = $deploy['ali']['appid'];
                $config->signType = 'RSA2';
                $config->alipayPublicKey = $deploy['ali']['public'];
                $config->merchantPrivateKey = $deploy['ali']['private'];
                Factory::setOptions($config);
                //单据数据
                $result = Factory::payment()->faceToFace()->pay($deploy['ali']['title'],$input['number'],$input['money'],$input['code']);
                if($result->code=='10000'){
                    //支付成功
                    $result=['state'=>'success','info'=>$result->tradeNo];
                }else{
                    //支付失败
                    if(in_array($result->code,['10003','20000'])){
                        //返回等待信息
                        $result=['state'=>'wait','info'=>'等待操作...'];
                    }else{
                        //确认失败，返回错误信息
                        $result=['state'=>'wrong','info'=>$result->subMsg];
                    }
                }
            }else{
                $result=['state'=>'error','info'=>'支付参数不完整!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //查询单据
    public function query(){
        $input=input('post.');
        if(existFull($input,['number'])){
            //读取配置
            $deploy=getFrameDeploy();
            if(!empty($deploy)){
                //配置数据
                $config=new Config;
                $config->protocol = 'https';
                $config->gatewayHost = 'openapi.alipay.com';
                $config->appId = $deploy['ali']['appid'];
                $config->signType = 'RSA2';
                $config->alipayPublicKey = $deploy['ali']['public'];
                $config->merchantPrivateKey = $deploy['ali']['private'];
                Factory::setOptions($config);
                //单据数据
                $result = Factory::payment()->common()->query($input['number']);
                //调用结果
                if($result->code=='10000'){
                    if(in_array($result->tradeStatus,['TRADE_SUCCESS','TRADE_FINISHED'])){
                        //支付成功
                        $result=['state'=>'success','info'=>$result->tradeNo];
                    }elseif($result->tradeStatus=='WAIT_BUYER_PAY'){
                        //返回等待信息
                        $result=['state'=>'wait','info'=>'等待操作...'];
                    }else{
                        $result=['state'=>'wrong','info'=>'未付款|支付超时|已撤销|已退款'];
                    }
                }else{
                    //确认失败，返回错误信息
                    $result=['state'=>'wrong','info'=>$result->subMsg];
                }
            }else{
                $result=['state'=>'error','info'=>'支付参数不完整!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //撤销单据
    //支付成功退款|未支付关闭单据
    public function cancel(){
        $input=input('post.');
        if(existFull($input,['number'])){
            //读取配置
            $deploy=getFrameDeploy();
            if(!empty($deploy)){
                //配置数据
                $config=new Config;
                $config->protocol = 'https';
                $config->gatewayHost = 'openapi.alipay.com';
                $config->appId = $deploy['ali']['appid'];
                $config->signType = 'RSA2';
                $config->alipayPublicKey = $deploy['ali']['public'];
                $config->merchantPrivateKey = $deploy['ali']['private'];
                Factory::setOptions($config);
                //单据数据
                $result = Factory::payment()->common()->cancel($input['number']);
                //调用结果
                if($result->code=='10000'){
                    if(in_array($result->action,['close','refund'])){
                        //撤销成功
                        $result=['state'=>'success','info'=>'撤销单据成功!'];
                    }else{
                        $result=['state'=>'wrong','info'=>'撤销单据失败,请人工处理!'];
                    }
                }else{
                    //确认失败，返回错误信息
                    $result=['state'=>'wrong','info'=>$result->subMsg];
                }
            }else{
                $result=['state'=>'error','info'=>'支付参数不完整!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}