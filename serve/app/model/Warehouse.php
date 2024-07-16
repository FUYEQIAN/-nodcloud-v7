<?php
namespace app\model;
use	think\Model;
class Warehouse extends Model{
    //仓库
    
    //组织属性关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }

}
