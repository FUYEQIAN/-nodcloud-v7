<?php
namespace app\model;
use	think\Model;
class ImyBill extends Model{
    //收款单核销详情
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    
    //关联单据
    public function sourceData(){
        return $this->hasOne(Bill::class,'id','source');
    }
    
    //核销金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']='核销单';
        return $source;
	}
}
