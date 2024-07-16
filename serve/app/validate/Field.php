<?php
namespace app\validate;
use think\Validate;
class Field extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'key' => ['require','unique:field'],
        'fields' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '模块名称不可为空!',
        'key.require' => '模块标识不可为空!',
        'key.unique' => '模块标识重复!',
        'fields.array' => '字段数据不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','key','fields']
    ];
}