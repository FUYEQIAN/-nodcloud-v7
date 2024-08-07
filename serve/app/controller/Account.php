<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Account as Accounts;
use think\facade\Db;
use think\exception\ValidateException;
class Account extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['name','fullLike'],
                ['number','fullLike'],
                ['data','fullLike']
            ]);//构造SQL
            $sql=frameScope($sql);//组织数据
            $sql=sqlAuth('account',$sql);//数据鉴权
            $count = Accounts::where($sql)->count();//获取总条数
            $info = Accounts::with(['frameData'])->where($sql)->append(['extension'])->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //构造|验证
            try {
                //排除balance字段|防止更新账户余额
                unset($input['balance']);
                empty($input['id'])?$this->validate($input,'app\validate\Account'):$this->validate($input,'app\validate\Account.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Accounts::create($input);
                    pushLog('新增资金账户[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Accounts::update($input);
                    pushLog('更新资金账户[ '.$input['name'].' ]');//日志
                }
                
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
    //获取
    public function get(){
        $input=input('post.');
        if(existFull($input,['id'])){
            $info=Accounts::where([['id','=',$input['id']]])->find();
            $result=['state'=>'success','info'=>$info];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['id'])){
            //关联判断
            $exist=moreTableFind([
                ['table'=>'sell','where'=>[['account','=',$input['id']]]],
                ['table'=>'sre','where'=>[['account','=',$input['id']]]],
                ['table'=>'buy','where'=>[['account','=',$input['id']]]],
                ['table'=>'bre','where'=>[['account','=',$input['id']]]],
                ['table'=>'ice','where'=>[['account','=',$input['id']]]],
                ['table'=>'oce','where'=>[['account','=',$input['id']]]],
                ['table'=>'allot_info','where'=>[['account|tat','=',$input['id']]]]
            ]);
            if(empty($exist)){
                //逻辑处理
                $find=Db::name('account')->where([['id','=',$input['id']]])->find();
                Db::startTrans();
                try {
                    Db::name('account')->where([['id','=',$input['id']]])->delete();
                    Db::name('account_info')->where([['pid','=',$input['id']]])->delete();
                    pushLog('删除资金账户[ '.$find['name'].' ]');//日志
                    
                	Db::commit();
                	$result=['state'=>'success'];
                } catch (\Exception $e) {
                	Db::rollback();
                	$result=['state'=>'error','info'=>'内部错误,操作已撤销!'];
                }
            }else{
                $result=['state'=>'error','info'=>'存在数据关联,删除失败!'];
            }
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
}