<?php
/*
BY:NODCLOUD.COM
*/
namespace org;
class Math{
    private $base;
    
    //入口
    public function __construct($digit){
        bcscale($digit);
    }
    //基础数值
    public function chain($number) {
        $this->base=$number;
        return $this;
    }
    //加法运算
    public function add($number) {
        $this->base=bcadd($this->base,$number);
        return $this;
    }
    //减法运算
    public function sub($number) {
        $this->base=bcsub($this->base,$number);
        return $this;
    }
    //乘法运算
    public function mul($number) {
        $this->base=bcmul($this->base,$number);
        return $this;
    }
    //除法运算
    public function div($number) {
        $this->base=bcdiv($this->base,$number);
        return $this;
    }
    //取余运算
    public function mod($number) {
        $this->base=bcmod($this->base,$number);
        return $this;
    }
    //四舍五入
    public function round($digit) {
        $this->base=round($this->base,$digit);
        return $this;
    }
    //四舍五入
    public function abs() {
        $this->base=abs($this->base);
        return $this;
    }
    //返回结果
    public function done() {
        return floatval($this->base);
    }
}
