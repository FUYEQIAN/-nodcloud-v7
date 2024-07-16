<?php
namespace app\validate;
use think\Validate;
class Warehouse extends Validate {
    //常规验证规则
    protected $rule = [
        'name' => ['require','unique:warehouse'],
        'number' => ['require','unique:warehouse'],
        'frame' => ['require','integer']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '仓库名称不可为空!',
        'name.unique' => '仓库名称重复!',
        'number.require' => '仓库编号不可为空!',
        'number.unique' => '仓库编号重复!',
        'frame.require' => '所属组织不可为空!',
        'frame.integer' => '所属组织不正确!'
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','number','frame']
    ];
}