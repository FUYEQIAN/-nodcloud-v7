<?php
require_once "WxPay.Config.Interface.php";
class WxPayConfig extends WxPayConfigInterface{
    public $appId = "";
    public $merchantId = "";
    public $notifyUrl = "";
    public $signType = "HMAC-SHA256";
    public $proxyHost = "0.0.0.0";
    public $proxyPort = 0;
    public $reportLevenl = 1;
    public $key = "";
    public $appSecret = "";
    public $sslCertPath = "apiclient_cert.pem";
    public $sslKeyPath = "apiclient_key.pem";
    
    //商户ID
	public function GetAppId(){
		return $this->appId;
	}
	
	//商户号
	public function GetMerchantId(){
		return $this->merchantId;
	}
	
	//异步通知地址
	public function GetNotifyUrl(){
		return $this->notifyUrl;
	}
	
	//签名方式
	public function GetSignType(){
		return $this->signType;
	}
	
	//代理信息
	public function GetProxy(&$proxyHost, &$proxyPort){
		$proxyHost = $this->proxyHost;
		$proxyPort = $this->proxyPort;
	}
	
	//错误上报等级
	public function GetReportLevenl(){
		return $this->reportLevenl;
	}
	
	//支付密钥
	public function GetKey(){
		return $this->key;
	}
	
	//公众帐号secert
	public function GetAppSecret(){
		return $this->appSecret;
	}
	
	//证书路径
	public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath){
		$sslCertPath = $this->sslCertPath;
		$sslKeyPath = $this->sslKeyPath;
	}
}
