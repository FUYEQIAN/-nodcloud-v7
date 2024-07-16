<?php
namespace app\controller ;
use app\controller\Acl;
use app\model\Deploy as Deploys;
use think\facade\Db;
use think\exception\ValidateException;
class Deploy extends Acl {
    //列表
    public function record(){
        $input=input('post.');
        $sql=fastSql($input,[
            ['frame','noNullEq'],
            ['data','fullLike']
        ]);//构造SQL
        $count = Deploys::where($sql)->count();//获取总条数
        $info = Deploys::with(['frameData'])->where($sql)->page($input['page'],$input['limit'])->order(['id'=>'desc'])->select();//查询分页数据
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
                empty($input['id'])?$this->validate($input,'app\validate\Deploy'):$this->validate($input,'app\validate\Deploy.update');
            } catch (ValidateException $e) {
                return json(['state'=>'error','info'=>$e->getError()]);
                exit;
            }
            
            //处理数据
            Db::startTrans();
            try {
                if(empty($input['id'])){
                    //创建数据
                    Deploys::create($input);
                    pushLog('新增零售配置');//日志
                }else{
                    //更新数据
                    Deploys::update($input);
                    pushLog('更新零售配置');//日志
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
                'info'=>Deploys::where([['id','=',$input['id']]])->find()
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
            Db::startTrans();
            try {
                Db::name('deploy')->where([['id','=',$input['id']]])->delete();
                pushLog('删除零售配置');//日志
                
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