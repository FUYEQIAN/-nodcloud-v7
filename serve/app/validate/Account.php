<?php
namespace app\validate;
use think\Validate;
class Account extends Validate {
    
    //常规验证规则
    protected $rule = [
        'name' => ['require'],
        'number' => ['require','unique:account'],
        'frame' => ['require','integer'],
        'time' => ['require','date'],
        'initial' => ['require','float']
    ];

    //常规规则提示
    protected $message = [
        'name.require' => '账户名称不可为空!',
        'number.require' => '账户编号不可为空!',
        'number.unique' => '账户编号重复!',
        'frame.require' => '所属组织不可为空!',
        'frame.integer' => '所属组织不正确!',
        'time.require' => '余额日期不可为空!',
        'time.date' => '余额日期不正确!',
        'initial.require' => '期初余额不可为空!',
        'initial.float' => '期初余额不正确!',
    ];

    //场景规则
    protected $scene = [
        'update' => ['name','number','frame','time','initial']
    ];
}