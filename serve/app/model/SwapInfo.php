<?php
namespace app\model;
use	think\Model;
class SwapInfo extends Model{
    //调拨单详情
    
    protected $type = [
        'serial'=>'json'
    ];
    
    //商品关联
    public function goodsData(){
        return $this->hasOne(Goods::class,'id','goods');
    }
    
    //调出仓库关联
    public function warehouseData(){
        return $this->hasOne(Warehouse::class,'id','warehouse');
    }
    
    //调入仓库关联
    public function storehouseData(){
        return $this->hasOne(Warehouse::class,'id','storehouse');
    }
    
    //调出仓库_设置器
    public function setWarehouseAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //调入仓库_设置器
    public function setStorehouseAttr($val,$data){
        return empty($val)?0:$val;
    }
    
    //调出仓库_读取器
    public function getWarehouseAttr($val,$data){
        return empty($val)?null:$val;
    }
    
    //调入仓库_读取器
    public function getStorehouseAttr($val,$data){
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
	
	//成本_读取器
	public function getPriceAttr($val,$data){
        return floatval($val);
	}
	
	//数量_读取器
	public function getNumsAttr($val,$data){
        return floatval($val);
	}
	
	//总成本_读取器
	public function getTotalAttr($val,$data){
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
