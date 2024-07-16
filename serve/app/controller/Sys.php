<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Sys as Syss;
use think\facade\Db;
class Sys extends Acl {
    //列表
    public function record(){
        return json(['state'=>'success','info'=>getSys()]);
    }
    //保存
    public function save(){
        $input=input('post.');
        if(is_array($input)){
            $sql=[];
            //查找对应主键
            $keys=array_keys($input);
            $sys=Syss::where([['name','in',$keys]])->field(['id','name'])->select();
            //构造数据
            foreach ($sys as $row){
                $sql[]=['id'=>$row['id'],'info'=>$input[$row['name']]];
            }
            Db::startTrans();
            try {
                //数据处理
                $model=new Syss;
                $model->saveAll($sql);
                pushLog('修改系统参数');
                
                Db::commit();
                $result=['state'=>'success'];
            } catch (\Exception $e) {
                Db::rollback();
                $result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}