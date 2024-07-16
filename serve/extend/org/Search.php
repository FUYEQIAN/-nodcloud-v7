<?php
/*
BY:NODCLOUD.COM
*/
namespace org;
class Search{
    //数据源
    private $source;
    
    //初始化
    public function __construct($source=[]) {
        $this->source=$source;
        return $this;
    }
    
    //搜索条件
    //[['key|key1','=|<>|in|like|between','val']]
    public function where($condition,$retain=false) {
        $recode=[];
        foreach ($this->source as $sourcekey=>$sourceVo) {
            $state=true;
            foreach ($condition as $conditionVo){
				//处理多层键名
				$row=$this->arraySeek($sourceVo,$conditionVo[0]);
                //条件判断
                if($conditionVo[1]=='='){
                    //相等判断
                    $row==$conditionVo[2]||($state=false);
                }elseif($conditionVo[1]=='<>'){
                    //不相等判断
                    $row==$conditionVo[2]&&($state=false);
                }elseif($conditionVo[1]=='in'){
                    //包含判断
                    in_array($row,$conditionVo[2])||($state=false);
                }elseif($conditionVo[1]=='like'){
                    //模糊匹配判断
                    strstr($row,$conditionVo[2])==false&&($state=false);
                }elseif($conditionVo[1]=='between'){
                    //区间判断
                    ($row>=$conditionVo[2][0] && $row<=$conditionVo[2][1])||($state=false);
                }else{
                    die('匹配规则失败!');
                }
            }
            if($state){
                $retain&&$sourceVo['rowKey']=$sourcekey;
                $recode[]=$sourceVo;
            }
        }
        $this->source=$recode;
        return $this;
    }
    //处理数据
    public function loop($fun){
        foreach ($this->source as $key=>$vo) {
            $this->source[$key]=$fun($vo,$key);
        }
        return $this;
    }
    
    //单组数据
    public function find() {
        return empty($this->source)?[]:$this->source[0];
    }
    
    //多组数据
    public function select() {
        return $this->source;
    }
    
    //数据统计
    public function count() {
        return count($this->source);
    }
    
    //多层键名匹配
    private function arraySeek($data,$rule){
        $recode=$data;
        is_array($rule)||($rule=explode('|',$rule));
        foreach ($rule as $ruleVo) {
            if(is_array($recode) && isset($recode[$ruleVo])){
                $recode=$recode[$ruleVo];
            }else{
                $recode='';
                break;
            }
        }
        return $recode;
    }
}
