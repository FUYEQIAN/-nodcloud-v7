<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Iet as Iets;
use think\facade\Db;
use think\exception\ValidateException;
class Iet extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            ['name','fullLike'],
            ['type','fullDec1']
        ]);//构造SQL
        $count = Iets::where($sql)->count();//获取总条数
        $info = Iets::where($sql)->append(['extension'])->order(['sort'=>'asc', 'id'=>'desc'])->select();//查询分页数据
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
            //构造|验证
            try {
                empty($input['id'])?$this->validate($input,'app\validate\Iet'):$this->validate($input,'app\validate\Iet.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Iets::create($input);
                    pushLog('新增收支类别[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Iets::update($input);
                    pushLog('更新收支类别[ '.$input['name'].' ]');//日志
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
            $info=Iets::where([['id','=',$input['id']]])->find();
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
                ['table'=>'cost','where'=>[['iet','=',$input['id']]]],
                ['table'=>'ice_info','where'=>[['iet','=',$input['id']]]],
                ['table'=>'oce_info','where'=>[['iet','=',$input['id']]]],
            ]);
            if(empty($exist)){
                //逻辑处理
                $find=Db::name('iet')->where([['id','=',$input['id']]])->find();
                Db::startTrans();
                try {
                    Db::name('iet')->where([['id','=',$input['id']]])->delete();
                    pushLog('删除收支类别[ '.$find['name'].' ]');//日志
                    
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