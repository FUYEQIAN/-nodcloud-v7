<?php
namespace app\model;
use	think\Model;
class Attr extends Model{
    //辅助属性[商品]
    
	//采购价格_读取器
	public function getBuyAttr($val,$data){
        return floatval($val);
	}
	
	//销售价格_读取器
	public function getSellAttr($val,$data){
        return floatval($val);
	}
	
	//零售价格_读取器
	public function getRetailAttr($val,$data){
        return floatval($val);
	}
}