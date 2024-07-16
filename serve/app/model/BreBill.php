<?php
namespace app\model;
use	think\Model;
class BreBill extends Model{
    //采购退货单核销详情
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    
    //结算账户关联
    public function sourceData(){
        return $this->morphTo(['type','source'],[
            'bre'   =>  Bre::class,
            'bill'  =>  Bill::class
        ]);
    }
    
    //核销金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']=['bre'=>'采购退货单','bill'=>'核销单'][$data['type']];
        return $source;
	}
}
