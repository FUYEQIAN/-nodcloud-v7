<?php
namespace app\validate;
use think\Validate;
class People extends Validate {

    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'number' => ['require','unique:people'],
        'frame' => ['require','integer'],
        'sex' => ['require','integer'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '人员名称不可为空!',
        'number.require' => '人员编号不可为空!',
        'number.unique' => '人员编号重复!',
        'frame.require' => '所属组织不可为空!',
        'frame.integer' => '所属组织不正确!',
        'sex.require' => '性别不可为空!',
        'sex.integer' => '性别不正确!',
        'more.array' => '扩展信息不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','number','frame','sex','more'],
    ];
}