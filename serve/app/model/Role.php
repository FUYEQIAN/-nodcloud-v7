<?php
namespace app\model;
use	think\Model;
class Role extends Model{
    //用户角色
    protected $type = [
        'root' => 'json',
        'auth' => 'json'
    ];
}
