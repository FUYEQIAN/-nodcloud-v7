<?php
namespace app\model;
use	think\Model;
class Period extends Model{
    //结账记录
    
    //数据类型转换
    protected $type = [
        'date'=>'timestamp:Y-m-d',
        'time'=>'timestamp:Y-m-d',
    ];
    
    //用户属性关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
}
