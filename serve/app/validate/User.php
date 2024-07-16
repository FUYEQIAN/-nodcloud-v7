<?php
namespace app\validate;
use think\Validate;
class User extends Validate {

    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'frame' => ['require','integer'],
        'role' => ['integer'],
        'user' => ['require','unique:user'],
        'tel' => ['require','unique:user'],
        'pwd' => ['require'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '用户名称不可为空!',
        'frame.require' => '所属组织不可为空!',
        'frame.integer' => '所属组织不正确!',
        'role.integer' => '用户角色不正确!',
        'user.require' => '用户账号不可为空!',
        'user.unique' => '用户账号重复!',
        'tel.require' => '手机号码不可为空!',
        'tel.unique' => '手机号码重复!',
        'pwd.require' => '用户密码不可为空!',
        'more.array' => '扩展信息不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','frame','role','user','tel','more']
    ];
}