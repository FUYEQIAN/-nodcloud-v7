<?php
namespace app\validate;
use think\Validate;
class Menu extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'key' => ['require','unique:menu'],
        'pid' => ['require','integer'],
        'type' => ['require','integer'],
		'sort' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '菜单名称不可为空!',
        'key.require' => '菜单标识不可为空!',
        'key.unique' => '菜单标识重复!',
        'pid.require' => '所属菜单不可为空!',
        'pid.integer' => '所属菜单不正确!',
        'type.require' => '菜单类型不可为空!',
        'type.integer' => '菜单类型不正确!',
        'sort.require' => '菜单排序不可为空!',
		'sort.integer' => '菜单排序不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','key','pid','type','sort']
    ];
}