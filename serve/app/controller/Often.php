<?php
namespace app\controller;
use app\controller\Acl;
use think\facade\Db;
use think\exception\ValidateException;
class Often extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=[['user','=',getUserID()]];//构造SQL
        $info = Db::name('often')->where($sql)->select();//查询分页数据
        $result=[
            'state'=>'success',
            'info'=>$info
        ];//返回数据
        return json($result);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['parm'])){
            $userID=getUserID();
            //处理数据
            Db::startTrans();
            try {
                Db::name('often')->where([['user','=',$userID]])->delete();
                
                $insert=[];
                foreach ($input['parm'] as $v) {
                    $insert[]=['user'=>$userID,'name'=>$v['name'],'key'=>$v['key']];
                }
                empty($insert)||Db::name('often')->insertAll($insert);
                
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