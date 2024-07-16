<?php
namespace app\validate;
use think\Validate;
class Category extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'pid' => ['require','integer'],
		'sort' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '类别名称不可为空!',
        'pid.require' => '所属类别不可为空!',
        'pid.integer' => '所属类别不正确!',
        'sort.require' => '类别排序不可为空!',
		'sort.integer' => '类别排序不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','pid','sort']
    ];
}