<?php
namespace app\model;
use	think\Model;
class OmyInfo extends Model{
    //付款单详情
    
    //结算账户关联
    public function accountData(){
        return $this->hasOne(Account::class,'id','account');
    }
    
    //结算账户_设置器
    public function setAccountAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //结算账户_读取器
    public function getAccountAttr($val,$data){
        return empty($val)?null:$val;
    }
	
	//结算金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
}
