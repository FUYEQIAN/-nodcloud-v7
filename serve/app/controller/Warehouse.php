<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\Warehouse as Warehouses;
use think\facade\Db;
use think\exception\ValidateException;
class Warehouse extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            ['name','fullLike'],
            ['number','fullLike'],
            ['contacts','fullLike'],
            ['tel','fullLike'],
            ['add','fullLike'],
            ['data','fullLike']
        ]);//构造SQL
        $sql=frameScope($sql);//组织数据
        $sql=sqlAuth('warehouse',$sql);//数据鉴权
        $count = Warehouses::where($sql)->count();//获取总条数
        $info = Warehouses::with(['frameData'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
        $result=[
            'state'=>'success',
            'count'=>$count,
            'info'=>$info
        ];//返回数据
        return json($result);
    }
    //新增|更新
    public function save(){
        $input=input('post.');
        if(isset($input['id'])){
            //验证数据
            try {
                empty($input['id'])?$this->validate($input,'app\validate\Warehouse'):$this->validate($input,'app\validate\Warehouse.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Warehouses::create($input);
                    pushLog('新增仓库[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Warehouses::update($input);
                    pushLog('更新仓库[ '.$input['name'].' ]');//日志
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
            $result=[
                'state'=>'success',
                'info'=>Warehouses::where([['id','=',$input['id']]])->find()
            ];
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
                ['table'=>'sor_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'sell_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'sre_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'bor_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'buy_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'bre_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'extry_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'entry_info','where'=>[['warehouse','=',$input['id']]]],
                ['table'=>'swap_info','where'=>[['warehouse|storehouse','=',$input['id']]]]
            ]);
            if(empty($exist)){
                //逻辑处理
                $find=Db::name('warehouse')->where([['id','=',$input['id']]])->find();
                Db::startTrans();
                try {
                    Db::name('warehouse')->where([['id','=',$input['id']]])->delete();
                    pushLog('删除仓库[ '.$find['name'].' ]');//日志
                    
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