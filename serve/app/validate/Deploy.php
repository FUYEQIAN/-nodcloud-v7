<?php
namespace app\validate;
use think\Validate;
class Deploy extends Validate {
    //常规验证规则
    protected $rule = [
        'frame' => ['require','integer','unique:deploy'],
        'source' => ['require','array']
    ];

    //常规规则提示
    protected $message = [
        'frame.require' => '关联组织不可为空!',
        'frame.integer' => '关联组织不正确!',
        'frame.unique' => '关联组织重复!',
        'source.require' => '配置信息不可为空!',
        'source.array' => '配置信息不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['frame','source']
    ];
}