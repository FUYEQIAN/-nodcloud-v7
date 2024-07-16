<?php
namespace app\model;
use	think\Model;
class Serial extends Model{
    //序列号
    
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //条码类型
        $source['state']=[0=>'未销售',1=>'已销售',2=>'已调拨',3=>'已退货'][$data['state']];
        return $source;
	}
}
