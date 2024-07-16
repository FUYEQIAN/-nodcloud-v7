<?php
namespace app\model;
use	think\Model;
class BuyInfo extends Model{
    //采购单详情
    
    protected $type = [
        'serial'=>'json'
    ];
    
    //商品关联
    public function goodsData(){
        return $this->hasOne(Goods::class,'id','goods');
    }
    
    //仓库关联
    public function warehouseData(){
        return $this->hasOne(Warehouse::class,'id','warehouse');
    }
    
    //仓库_设置器
    public function setWarehouseAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //仓库_读取器
    public function getWarehouseAttr($val,$data){
        return empty($val)?null:$val;
    }
    
    //生产日期_设置器
	public function setMfdAttr($val,$data){
	    return empty($val)?0:strtotime($val);
	}
	
	//生产日期_读取器
	public function getMfdAttr($val,$data){
        return empty($val)?'':date('Y-m-d',$val);
	}
	
	//单价_读取器
	public function getPriceAttr($val,$data){
        return floatval($val);
	}
	
	//数量_读取器
	public function getNumsAttr($val,$data){
        return floatval($val);
	}
	
	//折扣率_读取器
	public function getDiscountAttr($val,$data){
        return floatval($val);
	}
	
	//折扣额_读取器
	public function getDscAttr($val,$data){
        return floatval($val);
	}
	
	//金额_读取器
	public function getTotalAttr($val,$data){
        return floatval($val);
	}
	
	//税率_读取器
	public function getTaxAttr($val,$data){
        return floatval($val);
	}
	
	//税额_读取器
	public function getTatAttr($val,$data){
        return floatval($val);
	}
	
	//价税合计_读取器
	public function getTptAttr($val,$data){
        return floatval($val);
	}
	
	//退货数量_读取器
	public function getRetreatAttr($val,$data){
        return floatval($val);
	}
	
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //序列号
        $source['serial']=implode(',',json_decode($data['serial']));
        return $source;
	}
}
