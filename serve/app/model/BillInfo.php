<?php
namespace app\model;
use	think\Model;
class BillInfo extends Model{
    //核销单详情
    
    //所属单据关联
    public function sourceData(){
        return $this->morphTo(['mold','source'],[
            'imy'   =>  Imy::class,
            'omy'  =>  Omy::class,
            'buy'  =>  Buy::class,
            'bre'  =>  Bre::class,
            'sell'  =>  Sell::class,
            'sre'  =>  Sre::class,
            'ice'  =>  Ice::class,
            'oce'  =>  Oce::class,
        ]);
    }
    
	//核销金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //核销类型
        $source['bill']=['cia'=>'预收','pia'=>'预付','re'=>'应收','cw'=>'应付','sre'=>'销退','sell'=>'销售','bre'=>'购退','buy'=>'采购'][$data['bill']];
        //单据类型
        $source['mold']=['imy'=>'收款单','omy'=>'付款单','buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单','ice'=>'其它收入单','oce'=>'其它支出单'][$data['mold']];
        return $source;
	}
}
