<?php
namespace app\validate;
use think\Validate;
class Mould extends Validate {

    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'key' => ['require'],
        'sort' => ['require','integer'],
        'source' => ['require','array'],
        'code' => ['require'],
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '模板名称不可为空!',
        'key.require' => '模板标识不可为空!',
        'sort.require' => '组织排序不可为空!',
		'sort.integer' => '组织排序不正确!',
        'source.require' => '数据源不可为空!',
        'source.array' => '数据源不正确!',
        'code.require' => '模板代码不可为空!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','key','sort','source','code']
    ];
}