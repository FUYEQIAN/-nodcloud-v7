<?php
namespace app\model;
use	think\Model;
class AccountInfo extends Model{
    //資金记录
    protected $type = [
        'time'=>'timestamp:Y-m-d'
    ];
    //单据关联
    public function sourceData(){
        return $this->morphTo(['type','class'],[
            'buy'=>Buy::class,
            'bre'=>Bre::class,
            'sell'=>Sell::class,
            'sre'=>Sre::class,
            'vend'=>Vend::class,
            'vre'=>Vre::class,
            'imy'=>Imy::class,
            'omy'=>Omy::class,
            'allotOut'=>Allot::class,
            'allotEnter'=>Allot::class,
            'ice'=>Ice::class,
            'oce'=>Oce::class,
        ]);
    }
    
    //金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']=['buy'=>'采购单', 'bre'=>'采购退货单', 'sell'=>'销售单', 'sre'=>'销售退货单', 'vend'=>'零售单', 'vre'=>'零售退货单', 'imy'=>'收款单', 'omy'=>'付款单', 'allotOut'=>'转账单-出', 'allotEnter'=>'转账单-入', 'ice'=>'其它收入单', 'oce'=>'其它支出单'][$data['type']];
        //操作类型
        $source['direction']=["减少","增加"][$data['direction']];
        
        return $source;
	}
}
