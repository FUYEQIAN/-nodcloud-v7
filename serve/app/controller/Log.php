<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Log as Logs;
use think\facade\Db;
class Log extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['info','fullLike'],
                ['user','fullEq'],
                [['startTime'=>'time'],'startTime'],
                [['endTime'=>'time'],'endTime']
            ]);//构造SQL
            $sql=sqlAuth('log',$sql);
            $count = Logs::where($sql)->count();//获取总条数
            $info = Logs::with(['userData'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
            $result=[
                'state'=>'success',
                'count'=>$count,
                'info'=>$info
            ];//返回数据
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //清空
    public function empty(){
        Db::startTrans();
        try {
            Db::query("truncate table is_log");
            pushLog('清空操作日志');//日志
            
        	Db::commit();
        	$result=['state'=>'success'];
        } catch (\Exception $e) {
        	Db::rollback();
        	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
        }
        return json($result);
    }
}
