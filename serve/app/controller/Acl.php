<?php
namespace app\controller;
use app\BaseController;
class Acl extends BaseController{
    //访问控制
    public function initialize() {
        if(!checkLogin()){
			exit(json(['state'=>'error','info'=>'访问由于凭证无效被拒绝!'],401)->send());
        }
    }
}