<?php
namespace app\model;
use	think\Model;
class AllotInfo extends Model{
    //转账单详情
    
    //转出账户关联
    public function accountData(){
        return $this->hasOne(Account::class,'id','account');
    }
    
    //转入账户关联
    public function tatData(){
        return $this->hasOne(Account::class,'id','tat');
    }
    
    //转出账户_设置器
    public function setAccountAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //转出账户_读取器
    public function getAccountAttr($val,$data){
        return empty($val)?null:$val;
    }
    
    //转入账户_设置器
    public function setTatAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //转入账户_读取器
    public function getTatAttr($val,$data){
        return empty($val)?null:$val;
    }
	
	//结算金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
}
