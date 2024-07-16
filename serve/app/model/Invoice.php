<?php
namespace app\model;
use think\Model;
class Invoice extends Model{
    //发票详情
    
    protected $type = [
        'time'=>'timestamp:Y-m-d',
        'file'=>'json'
    ];
    
    //单据关联
    public function sourceData(){
        return $this->morphTo(['type','class'],[
            'buy'   =>  Buy::class,
            'bre'  =>  Bre::class,
            'sell'   =>  Sell::class,
            'sre'  =>  Sre::class,
            'vend'   =>  Vend::class,
            'vre'  =>  Vre::class
        ]);
    }
    
    
    //开票金额_读取器
	public function getMoneyAttr($val,$data){
        return floatval($val);
	}
}
