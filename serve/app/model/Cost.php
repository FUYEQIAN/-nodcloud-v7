<?php
namespace app\model;
use	think\Model;
class Cost extends Model{
    //单据费用
    
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    
    //单据关联
    public function sourceData(){
        return $this->morphTo(['type','class'],[
            'buy'   =>  Buy::class,
            'bre'  =>  Bre::class,
            'sell'   =>  Sell::class,
            'sre'  =>  Sre::class,
            'vend'   =>  Vend::class,
            'barter'  =>  Barter::class,
            'vre'  =>  Vre::class,
            'swap'  =>  Swap::class,
            'entry'  =>  Entry::class,
            'extry'  =>  Extry::class
            
        ]);
    }
    
    //收支关联
    public function ietData(){
        return $this->hasOne(Iet::class,'id','iet')->field(['id','name']);
    }
	
    //金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
	//结算金额_读取器
	public function getSettleAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']=['buy'=>'采购单','bre'=>'采购退货单','sell'=>'销售单','sre'=>'销售退货单','vend'=>'零售单','vre'=>'零售退货单','barter'=>'积分兑换单','swap'=>'调拨单','entry'=>'其它入库单','extry'=>'其它出库单'][$data['type']];
        //结算状态
        $source['state']=[0=>'未结算',1=>'部分结算',2=>'已结算'][$data['state']];
        return $source;
	}
}
