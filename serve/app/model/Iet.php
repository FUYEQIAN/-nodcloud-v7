<?php
namespace app\model;
use think\Model;
class Iet extends Model{
    //收支类别
    
    
    //数据扩展
    public function getExtensionAttr($val,$data){
        $source=[];
        $source['type']=[0=>'收入',1=>'支出'][$data['type']];
        return $source;
    }
}
