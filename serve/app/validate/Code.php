<?php
namespace app\validate;
use think\Validate;
class Code extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'info' => ['require'],
        'type' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '条码名称不可为空!',
        'info.require' => '条码内容不可为空!',
        'type.require' => '条码类型不可为空!',
        'type.integer' => '条码类型不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','info','type']
    ];
}