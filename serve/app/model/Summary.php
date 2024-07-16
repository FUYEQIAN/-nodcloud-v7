<?php
namespace app\model;
use	think\Model;
class Summary extends Model{
    //库存记录
    
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
            'barter'=>Barter::class,
            'swapOut'=>Swap::class,
            'swapEnter'=>Swap::class,
            'entry'=>Entry::class,
            'extry'=>Extry::class,
        ]);
    }
    
    //仓库关联
    public function warehouseData(){
        return $this->hasOne(Warehouse::class,'id','warehouse');
    }
    
    //商品关联
    public function goodsData(){
        return $this->hasOne(Goods::class,'id','goods');
    }
    
    //基础单价_读取器
	public function getPriceAttr($val,$data){
        return floatval($val);
	}
	
	//基础数量_读取器
	public function getNumsAttr($val,$data){
        return floatval($val);
	}
	
	//单位成本_读取器
	public function getUctAttr($val,$data){
        return floatval($val);
	}
	
	//基础成本_读取器
	public function getBctAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //单据类型
        $source['type']=["buy"=>"采购单","bre"=>"采购退货单","sell"=>"销售单","sre"=>"销售退货单","vend"=>"零售单","vre"=>"零售退货单","barter"=>"积分兑换单","swapOut"=>"调拨单-出","swapEnter"=>"调拨单-入","entry"=>"其它入库单","extry"=>"其它出库单"][$data['type']];
        //操作类型
        $source['direction']=["减少","增加"][$data['direction']];
        
        return $source;
	}
}
