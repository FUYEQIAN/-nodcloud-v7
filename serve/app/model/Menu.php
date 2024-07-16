<?php
namespace app\model;
use	think\Model;
class Menu extends Model{
    //菜单
    
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //菜单模式
        $source['model']=[0=>'标签模式',1=>'新页模式'][$data['model']];
        //菜单类型
        $source['type']=[0=>'独立菜单',1=>'附属菜单'][$data['type']];
        return $source;
	}
}
