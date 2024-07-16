<?php
namespace app\controller;
use app\controller\Acl;
use app\model\Field as Fields;
use think\facade\Db;
use think\exception\ValidateException;
class Field extends Acl{
    //列表
    public function record(){
        $input=input('post.');
        if(existFull($input,['page','limit'])){
            $sql=fastSql($input,[
                ['name','fullLike'],
                ['key','fullLike']
            ]);//构造SQL
            $count = Fields::where($sql)->count();//获取总条数
            $info = Fields::where($sql)->page($input['page'],$input['limit'])->append(['extension'])->order(['id'=>'desc'])->select();//查询分页数据
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
            //验证数据
            try {
                empty($input['id'])?$this->validate($input,'app\validate\Field'):$this->validate($input,'app\validate\Field.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Fields::create($input);
                    pushLog('新增表单字段[ '.$input['name'].' ]');//日志
                }else{
                    //更新数据
                    Fields::update($input);
                    pushLog('更新表单字段[ '.$input['name'].' ]');//日志
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
                'info'=>Fields::where([['id','=',$input['id']]])->find()
            ];
        }else{
            $result=['state'=>'error','info'=>'传入参数不完整!'];
        }
        return json($result);
    }
    //删除
    public function del(){
        $input=input('post.');
        if(existFull($input,['parm']) && is_array($input['parm'])){
            $data=Db::name('field')->where([['id','in',$input['parm']]])->order(['id'=>'desc'])->select()->toArray();
            Db::startTrans();
            try {
                Db::name('field')->where([['id','in',$input['parm']]])->delete();
                Db::name('log')->insert(['time'=>time(),'user'=>getUserID(),'info'=>'删除表单字段[ '.implode(' | ',array_column($data,'name')).' ]']);
                
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
