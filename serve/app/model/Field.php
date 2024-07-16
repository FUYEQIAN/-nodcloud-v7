<?php
namespace app\model;
use	think\Model;
class Field extends Model{
    //表单字段
    
    //数据类型转换
    protected $type = [
        'fields'    =>  'json'
    ];
    
    
    
    //数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //模块标识
        $source['key']=["user"=>"用户管理","people"=>"人员管理","customer"=>"客户管理","supplier"=>"供应商管理","goods"=>"商品管理","bor"=>"采购订单","buy"=>"采购单","bre"=>"采购退货单","sor"=>"销售订单","sell"=>"销售单","sre"=>"销售退货单","vend"=>"零售单","vre"=>"零售退货单","barter"=>"积分兑换单","swap"=>"调拨单","entry"=>"其它入库单","extry"=>"其它出库单","imy"=>"收款单","omy"=>"付款单","bill"=>"核销单","allot"=>"转账单","ice"=>"其它收入单","oce"=>"其它支出单"][$data['key']];
        return $source;
	}
}
