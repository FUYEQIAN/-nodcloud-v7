<?php
namespace app\model;
use	think\Model;
class SerialInfo extends Model{
    //序列记录
    
    //单据关联
    public function sourceData(){
        return $this->morphTo(['type','class'],[
            'buy'=>Buy::class,
            'bre'=>Bre::class,
            'sell'=>Sell::class,
            'sre'=>Sre::class,
            'vend'=>Vend::class,
            'vre'=>Vre::class,
            'barter'=>Barter::class,
            'swapOut'=>Swap::class,
            'swapEnter'=>Swap::class,
            'entry'=>Entry::class,
            'extry'=>Extry::class,
        ]);
    }
    
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']=["buy"=>"采购单","bre"=>"采购退货单","sell"=>"销售单","sre"=>"销售退货单","vend"=>"零售单","vre"=>"零售退货单","barter"=>"积分兑换单","swapOut"=>"调拨单-出","swapEnter"=>"调拨单-入","entry"=>"其它入库单","extry"=>"其它出库单"][$data['type']];
        return $source;
	}
}
