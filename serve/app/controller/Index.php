<?php
namespace app\controller;
use think\facade\View;
class Index{
    public function index(){
        // 模板输出
        return View::fetch('index');
    }
}