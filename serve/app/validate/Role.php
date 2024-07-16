<?php
namespace app\validate;
use think\Validate;
class Role extends Validate {

    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'root' => ['require','array'],
        'auth' => ['require','array']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '角色名称不可为空!',
        'root.require' => '功能权限不可为空!',
        'root.array' => '功能权限不正确!',
        'auth.require' => '数据权限不可为空!',
        'auth.array' => '数据权限不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','root','auth']
    ];
}