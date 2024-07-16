<?php
namespace app\model;
use	think\Model;
class IceInfo extends Model{
    //其它收入单详情
    
    //收支关联
    public function ietData(){
        return $this->hasOne(Iet::class,'id','iet');
    }
    
    //所属组织关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
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
