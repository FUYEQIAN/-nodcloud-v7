<?php
namespace app\model;
use	think\Model;
class Log extends Model{
    //操作日志
    
    //数据类型转换
    protected $type = [
        'time'=>'timestamp:Y-m-d H:i:s',
    ];
    
    //用户属性关联
    public function userData(){
        return $this->hasOne(User::class,'id','user')->field(['id','name']);
    }
    
}
