<?php
namespace app\model;
use	think\Model;
class Record extends Model{
    //单据记录
    
    //所属用户关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
	//数据扩展
	public function getExtensionAttr($val,$data){
        $source=[];
        //时间文本
        $source['time']=date('Y-m-d H:i',$data['time']);
        return $source;
	}
}
