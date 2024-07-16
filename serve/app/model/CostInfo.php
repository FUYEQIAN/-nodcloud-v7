<?php
namespace app\model;
use	think\Model;
class CostInfo extends Model{
    //单据费用详情
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    
    //单据关联
    public function oceData(){
        return $this->hasOne(Oce::class,'id','oce');
    }
    
    //结算金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
}
