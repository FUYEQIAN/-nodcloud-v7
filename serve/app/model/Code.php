<?php
namespace app\model;
use	think\Model;
class Code extends Model{
    //条码管理
    
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //条码类型
        $source['type']=[0=>'条形码',1=>'二维码'][$data['type']];
        return $source;
	}
}
