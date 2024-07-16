<?php
namespace app\model;
use	think\Model;
class Deploy extends Model{
    //零售配置
    
    protected $type = [
        'source' => 'json'
    ];
    
    //组织属性关联
    public function frameData(){
        return $this->hasOne(Frame::class,'id','frame');
    }
}
