<?php
namespace app\validate;
use think\Validate;
class Iet extends Validate {
    
    //常规验证规则
    protected $rule = [
        'name' => ['require', 'unique:iet'],
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '类别名称不可为空!',
        'name.unique' => '类别名称不可重复!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name']
    ];
}