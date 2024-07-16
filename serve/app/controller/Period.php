<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Period as Periods;
use think\facade\Db;
class Period extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=sqlAuth('period',[]);
            $count = Periods::where($sql)->count();//获取总条数
            $info = Periods::with(['userData'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
    //结账
    public function save(){
        $input=input('post.');
        if(existFull($input,['date']) && strtotime($input['date'])){
            $period=getPeriod();
            $date=strtotime($input['date']);
            if($date>$period){
                $data=['date'=>$date,'time'=>time(),'user'=>getUserID()];
                Db::name('period')->insert($data);
                pushLog('结账操作');//日志
                
                $result=['state'=>'success'];
            }else{
                $result=['state'=>'error','info'=>'结账周期不正确!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //反结账
    public function back(){
        $period=getPeriod();
        $row=db('period')->where([['date','=',$period]])->delete();
        pushLog('反结账操作');//日志
        return json(['state'=>'success']);
    }
}
