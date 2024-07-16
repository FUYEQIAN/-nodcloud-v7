<?php
namespace app\model;
use	think\Model;
class Attribute extends Model{
    //辅助属性
    
    //子属性
    public function info(){
        return $this->hasMany(AttributeInfo::class,'pid','id')->visible(['name']);
    }
}
