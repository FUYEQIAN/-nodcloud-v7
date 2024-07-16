<?php
namespace app\model;
use	think\Model;
class OceBill extends Model{
    //其它支出单核销详情
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    
    //关联单据
    public function sourceData(){
        return $this->morphTo(['type','source'],[
            'oce'   =>  Oce::class,
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
        $source['type']=['oce'=>'其它支出单','bill'=>'核销单'][$data['type']];
        return $source;
	}
}
