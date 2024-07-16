<?php
namespace app\validate;
use think\Validate;
class Frame extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'pid' => ['require','integer'],
        'sort' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '组织名称不可为空!',
        'pid.require' => '所属组织不可为空!',
        'pid.integer' => '所属组织不正确!',
        'sort.require' => '组织排序不可为空!',
		'sort.integer' => '组织排序不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','pid','sort']
    ];
}