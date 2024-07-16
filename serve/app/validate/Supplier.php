<?php
namespace app\validate;
use think\Validate;
class Supplier extends Validate {
    
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'number' => ['require','unique:supplier'],
        'frame' => ['require','integer'],
        'user' => ['require','integer'],
        'rate' => ['require','between:0,100'],
        'contacts'=>['array'],
        'more' => ['array']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '供应商名称不可为空!',
        'number.require' => '供应商编号不可为空!',
        'number.unique' => '供应商编号重复!',
        'frame.require' => '所属组织不可为空!',
        'frame.integer' => '所属组织不正确!',
        'user.require' => '所属用户不可为空!',
        'user.integer' => '所属用户不正确!',
        'rate.require' => '增值税税率不可为空!',
        'rate.between' => '增值税税率不正确!',
        'contacts.array' => '联系资料不正确!',
        'more.array' => '扩展信息不正确!',

    ];

    //场景规则
    protected $scene = [
        'update' => ['name','number','frame','user','rate','contacts','more']
    ];
}