<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Deploy;
class Wechat extends Acl {
    //微信支付
    
    //付款码
    public function pay() {
        $input=input('post.');
        if(existFull($input,['number','money','code'])){
            //读取配置
            $deploy=getFrameDeploy();
            if(!empty($deploy)){
                //微信支付SDK
                $wxPayPath=root_path('extend/wechat');
                require_once $wxPayPath."WxPay.Api.php";
                require_once $wxPayPath."WxPay.Config.php";
                //配置数据
                $config=new \WxPayConfig;
                $config->appId=$deploy['wechat']['appid'];
                $config->merchantId=$deploy['wechat']['mchid'];
                $config->key=$deploy['wechat']['mchkey'];
                //单据数据
                $order=new \WxPayMicroPay;
                $order->SetBody($deploy['wechat']['title']);
                $order->SetOut_trade_no($input['number']);
                $money=math()->chain($input['money'])->mul(100)->done();
                $order->SetTotal_fee($money);
                $order->SetAuth_code($input['code']);
                //发送请求
                $result=\WxPayApi::micropay($config,$order);
                if($result['return_code']=='SUCCESS'){
                    //判断支付状态
                    if($result['result_code']=='SUCCESS'){
                        //支付成功
                        $result=['state'=>'success','info'=>$result['transaction_id']];
                    }else{
                        //支付失败
                        if(in_array($result['err_code'],['SYSTEMERROR','BANKERROR','USERPAYING'])){
                            //返回等待信息
                            $result=['state'=>'wait','info'=>'等待操作...'];
                        }else{
                            //确认失败，返回错误信息
                            $result=['state'=>'wrong','info'=>$result['err_code_des']];
                        }
                    }
                }else{
                    $result=['state'=>'wrong','info'=>$result['return_msg']];
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
                //微信支付SDK
                $wxPayPath=root_path('extend/wechat');
                require_once $wxPayPath."WxPay.Api.php";
                require_once $wxPayPath."WxPay.Config.php";
                //配置数据
                $config=new \WxPayConfig;
                $config->appId=$deploy['wechat']['appid'];
                $config->merchantId=$deploy['wechat']['mchid'];
                $config->key=$deploy['wechat']['mchkey'];
                //单据数据
                $order=new \WxPayOrderQuery;
                $order->SetOut_trade_no($input['number']);
                //发送请求
                $result=\WxPayApi::orderQuery($config,$order);
                if($result['return_code']=='SUCCESS'){
                    //判断查询状态
                    if($result['result_code']=='SUCCESS'){
                        //查询成功
                        if($result['trade_state']=='SUCCESS'){
                            //支付成功
                            $result=['state'=>'success','info'=>$result['transaction_id']];
                        }elseif($result['trade_state']=='USERPAYING'){
                            //用户支付中，返回等待信息
                            $result=['state'=>'wait','info'=>'等待操作...'];
                        }else{
                            //其他状态，返回数据
                            $result=['state'=>'wrong','info'=>$result['trade_state_desc']];
                        }
                    }else{
                        //查询失败
                        if($result['err_code']=='SYSTEMERROR'){
                            //返回等待信息
                            $result=['state'=>'wait','info'=>'等待操作...'];
                        }else{
                            //返回查询错误信息
                            $result=['state'=>'wrong','info'=>$result['err_code_des']];
                        }
                    }
                }else{
                    $result=['state'=>'wrong','info'=>$result['return_msg']];
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
                //微信支付SDK
                $wxPayPath=root_path('extend/wechat');
                require_once $wxPayPath."WxPay.Api.php";
                require_once $wxPayPath."WxPay.Config.php";
                $sslCert = tmpfile();
                fwrite($sslCert,$deploy['wechat']['certText']);
                $sslKey = tmpfile();
                fwrite($sslKey,$deploy['wechat']['keyText']);
                //配置数据
                $config=new \WxPayConfig;
                $config->appId=$deploy['wechat']['appid'];
                $config->merchantId=$deploy['wechat']['mchid'];
                $config->key=$deploy['wechat']['mchkey'];
                $config->sslCertPath=stream_get_meta_data($sslCert)['uri'];
                $config->sslKeyPath=stream_get_meta_data($sslKey)['uri'];
                //单据数据
                $order=new \WxPayReverse;
                $order->SetOut_trade_no($input['number']);
                //发送请求
                $result=\WxPayApi::reverse($config,$order);
                if($result['return_code']=='SUCCESS'){
                    //判断查询状态
                    if($result['result_code']=='SUCCESS'){
                        //撤销成功
                        $result=['state'=>'success','info'=>'撤销单据成功!'];
                    }else{
                        //查询失败
                        if(in_array($result['err_code'],['SYSTEMERROR','USERPAYING'])){
                            //等待信息
                            $result=['state'=>'wait','info'=>'等待操作...'];
                        }else{
                            //返回查询错误信息
                            $result=['state'=>'wrong','info'=>$result['err_code_des']];
                        }
                    }
                }else{
                    $result=['state'=>'wrong','info'=>$result['return_msg']];
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